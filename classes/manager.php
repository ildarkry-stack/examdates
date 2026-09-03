<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manager class for local_examdates plugin.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdates;

/**
 * Manager class for local_examdates plugin.
 *
 * Handles bulk-updating Quiz and Assignment exam/resit dates for a course category, previewing
 * changes before they're applied, logging changes and rolling them back.
 */
class manager {
    /** Number of courses shown on one web preview page. */
    public const PREVIEW_PAGE_SIZE = 50;

    /** Number of courses processed at once by background/CLI bulk operations. */
    public const PROCESS_BATCH_SIZE = 500;

    /**
     * Capability-aware category ID cache reused by count/page batch calls.
     *
     * @var array
     */
    private $categoryidcache = [];

    /**
     * Resolve category IDs the requested user may access.
     *
     * Keeping this in one helper ensures the paged course query and the matching
     * COUNT query use exactly the same category/capability scope.
     *
     * @param int $categoryid Category ID
     * @param bool $includesub Include subcategories
     * @param string $capability Capability to check
     * @param int|null $userid Check as this user rather than the current user
     * @return int[] Accessible category IDs
     */
    private function get_accessible_category_ids(
        $categoryid,
        $includesub,
        $capability,
        $userid
    ) {
        global $DB, $USER;

        $effectiveuserid = $userid === null ? (int)$USER->id : (int)$userid;
        $cachekey = implode(':', [
            (int)$categoryid,
            $includesub ? 1 : 0,
            $capability,
            $effectiveuserid,
        ]);
        if (isset($this->categoryidcache[$cachekey])) {
            return $this->categoryidcache[$cachekey];
        }

        $this->require_category_capability($categoryid, $capability, $userid);

        if (!$includesub) {
            $this->categoryidcache[$cachekey] = [(int)$categoryid];
            return $this->categoryidcache[$cachekey];
        }

        // Only inspect the selected branch rather than loading every category
        // on the site. Category paths are hierarchical (e.g. /1/7/24).
        $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,path', MUST_EXIST);
        $pathlike = $DB->sql_like('path', ':pathlike');
        $sql = "SELECT id
                  FROM {course_categories}
                 WHERE id = :categoryid
                    OR {$pathlike}
              ORDER BY depth ASC, id ASC";
        $categories = $DB->get_recordset_sql($sql, [
            'categoryid' => $categoryid,
            'pathlike' => $DB->sql_like_escape($category->path . '/') . '%',
        ]);

        $catids = [];
        foreach ($categories as $cat) {
            try {
                $this->require_category_capability($cat->id, $capability, $userid);
                $catids[] = (int)$cat->id;
            } catch (\required_capability_exception $e) {
                // No capability for this subcategory - skip it.
                continue;
            }
        }
        $categories->close();

        $this->categoryidcache[$cachekey] = $catids;
        return $this->categoryidcache[$cachekey];
    }

    /**
     * Get a bounded page/batch of courses in a category and optional subcategories.
     *
     * The limit is deliberately explicit so web requests never materialise an
     * arbitrarily large course array. Callers which need the full scope must
     * iterate in batches using count_courses_by_category().
     *
     * @param int $categoryid Category ID
     * @param bool $includesub Include subcategories
     * @param string $capability Capability to check
     * @param int|null $userid Check as this user rather than the current user
     * @param int $limitfrom Zero-based record offset
     * @param int $limitnum Maximum records to return
     * @return array Course records keyed by id
     */
    public function get_courses_by_category(
        $categoryid,
        $includesub = true,
        $capability = 'local/examdates:manage',
        $userid = null,
        $limitfrom = 0,
        $limitnum = self::PREVIEW_PAGE_SIZE
    ) {
        global $DB;

        $catids = $this->get_accessible_category_ids($categoryid, $includesub, $capability, $userid);
        if (empty($catids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'category');
        return $DB->get_records_select(
            'course',
            "category {$insql}",
            $params,
            'fullname ASC, id ASC',
            'id,category,fullname',
            max(0, (int)$limitfrom),
            max(1, (int)$limitnum)
        );
    }

    /**
     * Count courses in the same capability-aware scope used by the paged query.
     *
     * @param int $categoryid Category ID
     * @param bool $includesub Include subcategories
     * @param string $capability Capability to check
     * @param int|null $userid Check as this user rather than the current user
     * @return int Number of courses
     */
    public function count_courses_by_category(
        $categoryid,
        $includesub = true,
        $capability = 'local/examdates:manage',
        $userid = null
    ) {
        global $DB;

        $catids = $this->get_accessible_category_ids($categoryid, $includesub, $capability, $userid);
        if (empty($catids)) {
            return 0;
        }

        [$insql, $params] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'category');
        return (int)$DB->count_records_select('course', "category {$insql}", $params);
    }

    /**
     * Resolve configured Quiz and Assignment targets for each assessment period.
     *
     * The legacy quiz idnumber field remains the default target so existing
     * installations keep exactly the same behaviour after upgrading. Assignment
     * targets are opt-in: an empty assignment idnumber means "do not touch
     * assignments" for that period.
     *
     * @param object|null $data Prepared form/task data
     * @param bool $selectedonly Only return periods whose update checkbox is enabled
     * @return array Targets keyed by assessment type, then module name
     */
    private function get_activity_targets($data = null, $selectedonly = false) {
        $targets = [];

        foreach (['exam', 'resit1', 'resit2'] as $type) {
            if ($selectedonly && ($data === null || empty($data->{'update_' . $type}))) {
                continue;
            }

            $quizfield = $type . '_idnumber';
            $assignfield = $type . '_assign_idnumber';

            $quizidnumber = $type;
            if ($data !== null && property_exists($data, $quizfield)) {
                $quizidnumber = trim((string)$data->{$quizfield});
            }

            $assignidnumber = '';
            if ($data !== null && property_exists($data, $assignfield)) {
                $assignidnumber = trim((string)$data->{$assignfield});
            }

            $targets[$type] = [];
            if ($quizidnumber !== '') {
                $targets[$type]['quiz'] = $quizidnumber;
            }
            if ($assignidnumber !== '') {
                $targets[$type]['assign'] = $assignidnumber;
            }
        }

        return $targets;
    }

    /**
     * Preload matching activity instances for multiple courses and idnumbers.
     *
     * This keeps database reads outside the course/type loops and therefore
     * preserves the N+1 fix when Assignment support is enabled.
     *
     * @param array $courses List of course records
     * @param array $idnumbers Course-module idnumbers to find
     * @param string $modulename Supported module name: quiz or assign
     * @return array Matches indexed by [courseid][idnumber]
     */
    private function preload_module_instances($courses, $idnumbers, $modulename) {
        global $DB;

        $supportedmodules = ['quiz' => 'quiz', 'assign' => 'assign'];
        if (!isset($supportedmodules[$modulename])) {
            throw new \coding_exception('Unsupported activity module: ' . $modulename);
        }

        if (empty($courses) || empty($idnumbers)) {
            return [];
        }

        $courseids = [];
        foreach ($courses as $course) {
            if (!empty($course->id)) {
                $courseids[(int)$course->id] = (int)$course->id;
            }
        }

        $wantedidnumbers = [];
        foreach ($idnumbers as $idnumber) {
            $idnumber = trim((string)$idnumber);
            if ($idnumber !== '') {
                $wantedidnumbers[$idnumber] = $idnumber;
            }
        }

        if (empty($courseids) || empty($wantedidnumbers)) {
            return [];
        }

        [$idnumberinsql, $idnumberparams] = $DB->get_in_or_equal(
            array_values($wantedidnumbers),
            SQL_PARAMS_NAMED,
            'idnumber'
        );

        $result = [];
        $table = $supportedmodules[$modulename];

        // Keep each IN clause comfortably below database parameter limits.
        foreach (array_chunk(array_values($courseids), self::PROCESS_BATCH_SIZE) as $coursechunk) {
            [$courseinsql, $courseparams] = $DB->get_in_or_equal(
                $coursechunk,
                SQL_PARAMS_NAMED,
                'course'
            );

            $sql = "SELECT cm.id AS examdatescmid,
                           a.*,
                           cm.course AS examdatescourseid,
                           cm.idnumber AS examdatesidnumber
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                      JOIN {{$table}} a ON a.id = cm.instance
                     WHERE cm.course $courseinsql
                       AND cm.idnumber $idnumberinsql
                       AND m.name = :modulename
                       AND cm.deletioninprogress = 0
                  ORDER BY cm.id ASC";

            $params = array_merge($courseparams, $idnumberparams, ['modulename' => $modulename]);
            $records = $DB->get_records_sql($sql, $params);

            foreach ($records as $record) {
                $courseid = (int)$record->examdatescourseid;
                $idnumber = (string)$record->examdatesidnumber;
                $cmid = (int)$record->examdatescmid;

                // Keep the first module if a course contains duplicate idnumbers,
                // matching the behaviour of the previous single-record lookup.
                if (isset($result[$courseid][$idnumber])) {
                    continue;
                }

                unset($record->examdatescmid, $record->examdatescourseid, $record->examdatesidnumber);
                $result[$courseid][$idnumber] = [
                    'instance' => $record,
                    'cmid' => $cmid,
                ];
            }
        }

        return $result;
    }

    /**
     * Preload every selected activity module required by the current operation.
     *
     * @param array $courses List of course records
     * @param array $targets Targets from get_activity_targets()
     * @return array Matches keyed by module name
     */
    private function preload_activities($courses, $targets) {
        $idnumbers = ['quiz' => [], 'assign' => []];
        foreach ($targets as $moduletargets) {
            foreach ($moduletargets as $modulename => $idnumber) {
                $idnumbers[$modulename][$idnumber] = $idnumber;
            }
        }

        return [
            'quiz' => $this->preload_module_instances($courses, array_values($idnumbers['quiz']), 'quiz'),
            'assign' => $this->preload_module_instances($courses, array_values($idnumbers['assign']), 'assign'),
        ];
    }

    /**
     * Read the open/close pair represented by a supported activity module.
     *
     * For Assignment, the plugin treats "Allow submissions from" as open and
     * "Due date" as close. Cut-off and grading due dates are not used as the
     * displayed range.
     *
     * @param string $modulename quiz|assign
     * @param \stdClass $instance Activity record
     * @return array [open, close]
     */
    private function get_activity_dates($modulename, $instance) {
        if ($modulename === 'assign') {
            return [(int)$instance->allowsubmissionsfromdate, (int)$instance->duedate];
        }

        return [(int)$instance->timeopen, (int)$instance->timeclose];
    }

    /**
     * Get current Quiz and Assignment dates for preview.
     *
     * @param array $courses List of course records
     * @param object|null $idnumbers Prepared idnumber data
     * @param bool $selectedonly Only load periods selected for updating
     * @return array Current dates data keyed by courseid
     */
    public function get_current_dates($courses, $idnumbers = null, $selectedonly = false) {
        $targets = $this->get_activity_targets($idnumbers, $selectedonly);
        $activities = $this->preload_activities($courses, $targets);

        $result = [];
        foreach ($courses as $course) {
            $result[$course->id] = ['fullname' => $course->fullname];

            foreach ($targets as $type => $moduletargets) {
                $result[$course->id][$type] = [];
                foreach ($moduletargets as $modulename => $idnumber) {
                    $match = $activities[$modulename][$course->id][$idnumber] ?? null;
                    if (!$match) {
                        $result[$course->id][$type][$modulename] = [
                            'exists' => false,
                            'idnumber' => $idnumber,
                            'modulename' => $modulename,
                        ];
                        continue;
                    }

                    $instance = $match['instance'];
                    [$timeopen, $timeclose] = $this->get_activity_dates($modulename, $instance);
                    $result[$course->id][$type][$modulename] = [
                        'exists' => true,
                        'idnumber' => $idnumber,
                        'modulename' => $modulename,
                        'instanceid' => $instance->id,
                        'activityname' => $instance->name,
                        'timeopen' => $timeopen,
                        'timeclose' => $timeclose,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Build preview data with statistics for Quiz and Assignment activities.
     *
     * @param array $courses List of course records
     * @param object $newdates New dates data
     * @return array ['preview' => ..., 'stats' => ...]
     */
    public function get_preview_data($courses, $newdates) {
        $current = $this->get_current_dates($courses, $newdates, true);
        $targets = $this->get_activity_targets($newdates, true);

        $preview = [];
        $stats = [
            'total_courses' => count($courses),
            'courses_with_changes' => 0,
            'total_updates' => 0,
            'total_errors' => 0,
            'quiz_updates' => 0,
            'assign_updates' => 0,
            'quiz_missing' => 0,
            'assign_missing' => 0,
            'exam_updates' => 0,
            'resit1_updates' => 0,
            'resit2_updates' => 0,
        ];

        foreach ($current as $courseid => $data) {
            $coursepreview = ['fullname' => $data['fullname'], 'has_changes' => false];

            foreach ($targets as $type => $moduletargets) {
                $coursepreview[$type] = [];
                foreach ($moduletargets as $modulename => $idnumber) {
                    $activity = $data[$type][$modulename] ?? [
                        'exists' => false,
                        'idnumber' => $idnumber,
                        'modulename' => $modulename,
                    ];

                    if (empty($activity['exists'])) {
                        $coursepreview[$type][$modulename] = [
                            'status' => 'missing',
                            'idnumber' => $idnumber,
                        ];
                        $stats[$modulename . '_missing']++;
                        $stats['total_errors']++;
                        continue;
                    }

                    $oldopen = $activity['timeopen'];
                    $oldclose = $activity['timeclose'];
                    $newopen = (int)$newdates->{$type . 'open'};
                    $newclose = (int)$newdates->{$type . 'close'};

                    if ($oldopen != $newopen || $oldclose != $newclose) {
                        $coursepreview[$type][$modulename] = [
                            'status' => 'will_change',
                            'activityname' => $activity['activityname'],
                            'idnumber' => $idnumber,
                            'old_open' => $oldopen,
                            'old_close' => $oldclose,
                            'new_open' => $newopen,
                            'new_close' => $newclose,
                        ];
                        $coursepreview['has_changes'] = true;
                        $stats[$modulename . '_updates']++;
                        $stats[$type . '_updates']++;
                        $stats['total_updates']++;
                    } else {
                        $coursepreview[$type][$modulename] = [
                            'status' => 'no_change',
                            'activityname' => $activity['activityname'],
                            'idnumber' => $idnumber,
                            'old_open' => $oldopen,
                            'old_close' => $oldclose,
                        ];
                    }
                }
            }

            if ($coursepreview['has_changes']) {
                $stats['courses_with_changes']++;
            }

            $preview[$courseid] = $coursepreview;
        }

        return ['preview' => $preview, 'stats' => $stats];
    }

    /**
     * Render preview table.
     *
     * @param array $previewdata Preview data from get_preview_data()
     * @return string HTML
     */
    public function render_preview_table($previewdata) {
        $preview = $previewdata['preview'];
        $stats = $previewdata['stats'];

        $summary = \html_writer::start_div('alert alert-info mb-3');
        $summary .= \html_writer::tag('strong', get_string('preview_stats', 'local_examdates'));
        $summary .= \html_writer::empty_tag('br');
        $summary .= get_string('preview_activity_summary', 'local_examdates', (object)[
            'quizzes' => $stats['quiz_updates'],
            'assignments' => $stats['assign_updates'],
            'errors' => $stats['total_errors'],
        ]);
        $summary .= \html_writer::end_div();

        $table = new \html_table();
        $table->head = [
            get_string('course', 'local_examdates'),
            get_string('exam', 'local_examdates'),
            get_string('resit1', 'local_examdates'),
            get_string('resit2', 'local_examdates'),
        ];
        $table->data = [];

        foreach ($preview as $courseid => $data) {
            $courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
            $row = [\html_writer::link(
                $courseurl,
                format_string($data['fullname']),
                ['target' => '_blank']
            )];

            foreach (['exam', 'resit1', 'resit2'] as $type) {
                $row[] = $this->render_assessment_preview_cell($data[$type] ?? null);
            }
            $table->data[] = $row;
        }

        return $summary . \html_writer::table($table);
    }

    /**
     * Render the Quiz/Assignment rows within one assessment-period cell.
     *
     * @param array|null $activities Preview cells keyed by module name
     * @return string HTML
     */
    private function render_assessment_preview_cell($activities) {
        if (empty($activities)) {
            return \html_writer::tag(
                'span',
                '— ' . get_string('not_selected', 'local_examdates') . ' —',
                ['class' => 'text-muted']
            );
        }

        $parts = [];
        foreach (['quiz', 'assign'] as $modulename) {
            if (!isset($activities[$modulename])) {
                continue;
            }

            $labelkey = $modulename === 'quiz' ? 'quiz' : 'assignment';
            $parts[] = \html_writer::tag('strong', get_string($labelkey, 'local_examdates'))
                . ': ' . $this->render_preview_cell($activities[$modulename]);
        }

        return implode(\html_writer::empty_tag('br'), $parts);
    }

    /**
     * Render a single activity preview cell.
     *
     * @param array|null $cell
     * @return string HTML
     */
    private function render_preview_cell($cell) {
        if (!isset($cell)) {
            return \html_writer::tag(
                'span',
                '— ' . get_string('not_selected', 'local_examdates') . ' —',
                ['class' => 'text-muted']
            );
        }
        if ($cell['status'] === 'missing') {
            $label = get_string('notfound', 'local_examdates') . ' (' . s($cell['idnumber']) . ')';
            return \html_writer::tag('span', $label, ['class' => 'text-danger']);
        }

        $name = format_string($cell['activityname']) . ' (' . s($cell['idnumber']) . ')';
        $name = \html_writer::tag('small', $name, ['class' => 'd-block mb-1']);

        if ($cell['status'] === 'no_change') {
            $old = $this->format_date_range($cell['old_open'], $cell['old_close']);
            return $name . \html_writer::tag(
                'span',
                $old . ' ' . \html_writer::tag('small', '(' . get_string('nochanges', 'local_examdates') . ')'),
                ['class' => 'text-muted']
            );
        }
        if ($cell['status'] === 'will_change') {
            $old = $this->format_date_range($cell['old_open'], $cell['old_close']);
            $new = $this->format_date_range($cell['new_open'], $cell['new_close']);
            return $name . \html_writer::tag('span', $old, ['class' => 'text-warning'])
                . \html_writer::empty_tag('br')
                . get_string('arrow', 'local_examdates')
                . \html_writer::empty_tag('br')
                . \html_writer::tag('span', $new, ['class' => 'text-success font-weight-bold']);
        }
        return \html_writer::tag('span', '—', ['class' => 'text-muted']);
    }

    /**
     * Apply date updates to all matching Quiz and Assignment activities.
     *
     * @param array $courses List of course records
     * @param object $newdates Prepared data
     * @param int $userid User performing the change
     * @param string|null $batchid Optional id shared across multiple processing chunks
     * @param bool $triggerevent Whether to emit the completion event for this call
     * @return array ['updated' => [], 'errors' => [], 'skipped' => []]
     */
    public function apply_updates($courses, $newdates, $userid, $batchid = null, $triggerevent = true) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        if ($batchid === null) {
            $batchid = $this->create_batch_id();
        }
        $result = ['updated' => [], 'errors' => [], 'skipped' => []];

        $targets = $this->get_activity_targets($newdates, true);
        foreach ($targets as $type => $moduletargets) {
            if (empty($moduletargets)) {
                unset($targets[$type]);
                $result['errors'][] = get_string('activity_idnumber_required', 'local_examdates')
                    . ' (' . get_string($type, 'local_examdates') . ')';
                continue;
            }

            $open = (int)$newdates->{$type . 'open'};
            $close = (int)$newdates->{$type . 'close'};
            if ($close <= $open) {
                unset($targets[$type]);
                $result['errors'][] = get_string('invalid_dates', 'local_examdates')
                    . ' (' . get_string($type, 'local_examdates') . ')';
            }
        }

        $activities = $this->preload_activities($courses, $targets);

        // get_courses_by_category() normally supplies category. Batch-load any
        // missing values rather than introducing per-course database reads.
        $coursecategories = [];
        $missingcategorycourseids = [];
        foreach ($courses as $course) {
            if (isset($course->category)) {
                $coursecategories[(int)$course->id] = (int)$course->category;
            } else {
                $missingcategorycourseids[(int)$course->id] = (int)$course->id;
            }
        }
        if (!empty($missingcategorycourseids)) {
            $categoryrecords = $DB->get_records_list(
                'course',
                'id',
                array_values($missingcategorycourseids),
                '',
                'id,category'
            );
            foreach ($categoryrecords as $categoryrecord) {
                $coursecategories[(int)$categoryrecord->id] = (int)$categoryrecord->category;
            }
        }

        // Assignment calendar synchronisation needs full course and cm records.
        // Load them once for the whole processing batch, not from inside loops.
        $fullcourses = [];
        $assignmentcms = [];
        if (!empty($activities['assign'])) {
            $assignmentcourseids = [];
            $assignmentcmids = [];
            foreach ($activities['assign'] as $courseid => $matches) {
                $assignmentcourseids[$courseid] = $courseid;
                foreach ($matches as $match) {
                    $assignmentcmids[$match['cmid']] = $match['cmid'];
                }
            }
            if ($assignmentcourseids) {
                $fullcourses = $DB->get_records_list('course', 'id', array_values($assignmentcourseids));
            }
            if ($assignmentcmids) {
                foreach (array_chunk(array_values($assignmentcmids), self::PROCESS_BATCH_SIZE) as $cmchunk) {
                    $assignmentcms += $DB->get_records_list('course_modules', 'id', $cmchunk);
                }
            }
        }

        foreach ($courses as $course) {
            if (!isset($coursecategories[(int)$course->id])) {
                $result['errors'][] = get_string('error_coursedeleted', 'local_examdates')
                    . ': ' . format_string($course->fullname);
                continue;
            }

            $categoryid = $coursecategories[(int)$course->id];
            try {
                $this->require_category_capability($categoryid, 'local/examdates:manage', $userid);
            } catch (\required_capability_exception $e) {
                $result['errors'][] = get_string('error_nopermission', 'local_examdates')
                    . ': ' . format_string($course->fullname);
                continue;
            }

            $coursechanged = false;

            foreach ($targets as $type => $moduletargets) {
                foreach ($moduletargets as $modulename => $idnumber) {
                    $match = $activities[$modulename][$course->id][$idnumber] ?? null;
                    if (!$match) {
                        $result['errors'][] = get_string('missing_activity_idnumber', 'local_examdates', (object)[
                            'coursename' => format_string($course->fullname),
                            'activity' => get_string($modulename === 'quiz' ? 'quiz' : 'assignment', 'local_examdates'),
                            'idnumber' => s($idnumber),
                        ]);
                        continue;
                    }

                    $instance = $match['instance'];
                    [$oldopen, $oldclose] = $this->get_activity_dates($modulename, $instance);
                    $newopen = (int)$newdates->{$type . 'open'};
                    $newclose = (int)$newdates->{$type . 'close'};
                    $extra = [];

                    if ($modulename === 'assign') {
                        $oldextra = [
                            'cutoffdate' => (int)$instance->cutoffdate,
                            'gradingduedate' => (int)$instance->gradingduedate,
                        ];
                        $newextra = $oldextra;

                        // Keep enabled secondary dates valid if the due date moves
                        // beyond them, but do not enable a disabled date or shorten
                        // an existing late-submission/grading window.
                        if ($oldclose != $newclose) {
                            if ($newextra['cutoffdate'] > 0 && $newextra['cutoffdate'] < $newclose) {
                                $newextra['cutoffdate'] = $newclose;
                            }
                            if ($newextra['gradingduedate'] > 0 && $newextra['gradingduedate'] < $newclose) {
                                $newextra['gradingduedate'] = $newclose;
                            }
                        }
                        $extra = ['old' => $oldextra, 'new' => $newextra];
                    }

                    $extrachanged = !empty($extra) && $extra['old'] !== $extra['new'];
                    if ($oldopen == $newopen && $oldclose == $newclose && !$extrachanged) {
                        $result['skipped'][] = [
                            'courseid' => $course->id,
                            'coursename' => $course->fullname,
                            'activityname' => $instance->name,
                            'modulename' => $modulename,
                            'activitytype' => $type,
                        ];
                        continue;
                    }

                    if ($modulename === 'assign') {
                        $instance->allowsubmissionsfromdate = $newopen;
                        $instance->duedate = $newclose;
                        $instance->cutoffdate = $extra['new']['cutoffdate'];
                        $instance->gradingduedate = $extra['new']['gradingduedate'];
                    } else {
                        $instance->timeopen = $newopen;
                        $instance->timeclose = $newclose;
                    }
                    $instance->timemodified = time();
                    $DB->update_record($modulename, $instance);

                    $this->update_activity_calendar(
                        $modulename,
                        $instance,
                        $match['cmid'],
                        $fullcourses[$course->id] ?? null,
                        $assignmentcms[$match['cmid']] ?? null
                    );

                    $this->log_change(
                        $course,
                        $modulename,
                        $instance,
                        $idnumber,
                        $oldopen,
                        $oldclose,
                        $newopen,
                        $newclose,
                        $userid,
                        $batchid,
                        $extra
                    );

                    $coursechanged = true;
                    $result['updated'][] = [
                        'courseid' => $course->id,
                        'coursename' => $course->fullname,
                        'activityid' => $instance->id,
                        'activityname' => $instance->name,
                        'modulename' => $modulename,
                        'activitytype' => $type,
                        'idnumber' => $idnumber,
                        'old_timeopen_raw' => $oldopen,
                        'old_timeclose_raw' => $oldclose,
                        'new_timeopen_raw' => $newopen,
                        'new_timeclose_raw' => $newclose,
                        'old_dates' => $this->format_date_range($oldopen, $oldclose),
                        'new_dates' => $this->format_date_range($newopen, $newclose),
                    ];
                }
            }

            if ($coursechanged) {
                rebuild_course_cache($course->id, true);
            }
        }

        if ($triggerevent && !empty($result['updated'])) {
            $coursecount = count(array_unique(array_column($result['updated'], 'courseid')));
            $categoryid = isset($newdates->categoryid) ? (int)$newdates->categoryid : 0;
            $this->trigger_event($userid, $batchid, count($result['updated']), $categoryid, $coursecount);
        }

        return $result;
    }

    /**
     * Refresh calendar events after changing a supported activity instance.
     *
     * @param string $modulename quiz|assign
     * @param \stdClass $instance Updated activity record
     * @param int $cmid Course-module id
     * @param \stdClass|null $course Full course record when already preloaded
     * @param \stdClass|null $cm Full course-module record when already preloaded
     */
    private function update_activity_calendar($modulename, $instance, $cmid, $course = null, $cm = null) {
        if ($modulename === 'quiz') {
            if (function_exists('quiz_update_events')) {
                quiz_update_events($instance);
            }
            return;
        }

        if (function_exists('assign_prepare_update_events') && $course && $cm) {
            assign_prepare_update_events($instance, $course, $cm);
            return;
        }

        if (function_exists('assign_refresh_events')) {
            assign_refresh_events($instance->course, $instance, $cmid);
        }
    }

    /**
     * Create an identifier shared by all chunks of one logical bulk operation.
     *
     * @return string Batch identifier
     */
    public function create_batch_id() {
        return random_string(24);
    }

    /**
     * Emit one aggregate event after a chunked bulk operation finishes.
     *
     * @param int $userid User who made the change
     * @param string $batchid Shared batch identifier
     * @param int $count Number of activities updated
     * @param int $categoryid Selected category
     * @param int $coursecount Number of courses changed
     */
    public function trigger_batch_event($userid, $batchid, $count, $categoryid, $coursecount) {
        if ($count > 0) {
            $this->trigger_event($userid, $batchid, $count, $categoryid, $coursecount);
        }
    }

    /**
     * Log a single activity date change to the database.
     *
     * Honours the local_examdates/enable_logging setting (default: on).
     *
     * @param \stdClass $course Course record
     * @param string $modulename quiz|assign
     * @param \stdClass $activity Activity record after the update
     * @param string $idnumber Course-module idnumber
     * @param int $oldopen Previous open timestamp
     * @param int $oldclose Previous close timestamp
     * @param int $newopen New open timestamp
     * @param int $newclose New close timestamp
     * @param int $userid User performing the change
     * @param string $batchid Batch identifier shared by one apply/rollback run
     * @param array $extra Optional module-specific before/after values
     */
    private function log_change(
        $course,
        $modulename,
        $activity,
        $idnumber,
        $oldopen,
        $oldclose,
        $newopen,
        $newclose,
        $userid,
        $batchid,
        $extra = []
    ) {
        global $DB, $USER;

        if (get_config('local_examdates', 'enable_logging') === '0') {
            return;
        }

        $record = new \stdClass();
        $record->userid          = $userid ?: $USER->id;
        $record->timecreated     = time();
        $record->categoryid      = $course->category;
        $record->courseid        = $course->id;
        $record->course_fullname = $course->fullname;
        $record->modulename      = $modulename;
        $record->instanceid      = $activity->id;
        $record->activity_name   = $activity->name;

        // Keep the legacy columns populated for existing reports/upgrades. For
        // Assignment rows quizid is 0, while quiz_name still carries a readable
        // fallback name for older export code.
        $record->quizid          = $modulename === 'quiz' ? $activity->id : 0;
        $record->quiz_name       = $activity->name;
        $record->idnumber        = $idnumber;
        $record->old_timeopen    = $oldopen ?: 0;
        $record->old_timeclose   = $oldclose ?: 0;
        $record->new_timeopen    = $newopen;
        $record->new_timeclose   = $newclose;
        $record->extra_data      = empty($extra) ? null : json_encode($extra);
        $record->action_type     = (strpos($batchid, 'rollback_') === 0) ? 'rollback' : 'bulk';
        $record->batch_id        = $batchid;
        $record->ip_address      = getremoteaddr();

        $DB->insert_record('local_examdates_log', $record);
    }

    /**
     * Trigger the plugin's own event.
     *
     * @param int $userid User who made the change
     * @param string $batchid Batch identifier for this apply/rollback run
     * @param int $count Number of activity instances changed
     * @param int $categoryid Category the change was applied to
     * @param int $coursecount Number of distinct courses affected
     */
    private function trigger_event($userid, $batchid, $count, $categoryid = 0, $coursecount = 0) {
        $event = \local_examdates\event\dates_updated::create([
            'context' => \context_system::instance(),
            'userid'  => $userid,
            'other'   => [
                'batch_id'      => $batchid,
                'updates_count' => $count,
                'categoryid'    => $categoryid,
                'coursecount'   => $coursecount,
            ],
        ]);
        $event->trigger();
    }

    /**
     * Format a date range for display, respecting the user's timezone and locale.
     *
     * @param int $timeopen Timestamp
     * @param int $timeclose Timestamp
     * @return string
     */
    public function format_date_range($timeopen, $timeclose) {
        $format = get_string('strftimedatetime', 'langconfig');

        $openstr = empty($timeopen)
            ? get_string('no_limit', 'local_examdates')
            : userdate($timeopen, $format);

        $closestr = empty($timeclose)
            ? get_string('no_limit', 'local_examdates')
            : userdate($timeclose, $format);

        return $openstr . ' — ' . $closestr;
    }

    /**
     * Get the change history.
     *
     * @param array $filters Optional filters (courseid, userid, idnumber, from, to)
     * @param int $page Page number
     * @param int $perpage Items per page
     * @return array
     */
    public function get_history($filters = [], $page = 0, $perpage = 50) {
        global $DB;

        $params = [];
        $conditions = [];

        $map = ['courseid' => 'courseid', 'userid' => 'userid', 'idnumber' => 'idnumber'];
        foreach ($map as $key => $column) {
            if (!empty($filters[$key])) {
                $conditions[] = "$column = :$key";
                $params[$key] = $filters[$key];
            }
        }
        if (!empty($filters['from'])) {
            $conditions[] = 'timecreated >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $conditions[] = 'timecreated <= :to';
            $params['to'] = $filters['to'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $total = $DB->count_records_sql("SELECT COUNT(*) FROM {local_examdates_log} $where", $params);
        $records = $DB->get_records_sql(
            "SELECT * FROM {local_examdates_log} $where ORDER BY timecreated DESC, id DESC",
            $params,
            $page * $perpage,
            $perpage
        );

        return ['records' => $records, 'total' => $total, 'page' => $page, 'perpage' => $perpage];
    }

    /**
     * Roll back a specific activity change to its previous dates.
     *
     * @param int $logid Log record ID
     * @param int $userid User performing the rollback
     * @return bool True on success.
     * @throws \moodle_exception If the log entry, course or activity can no longer be found.
     * @throws \required_capability_exception If the user cannot manage dates in that category.
     */
    public function rollback_change($logid, $userid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $log = $DB->get_record('local_examdates_log', ['id' => $logid]);
        if (!$log) {
            throw new \moodle_exception('error_lognotfound', 'local_examdates');
        }

        $modulename = !empty($log->modulename) ? $log->modulename : 'quiz';
        $instanceid = !empty($log->instanceid) ? (int)$log->instanceid : (int)$log->quizid;
        if (!in_array($modulename, ['quiz', 'assign'], true) || $instanceid <= 0) {
            throw new \moodle_exception('error_activitydeleted', 'local_examdates');
        }

        // Only the most recent change for this exact activity instance may be
        // rolled back. This prevents an older rollback from discarding a newer
        // change to the same Quiz or Assignment.
        $latestid = $DB->get_field_sql(
            'SELECT MAX(id)
               FROM {local_examdates_log}
              WHERE modulename = :modulename AND instanceid = :instanceid',
            ['modulename' => $modulename, 'instanceid' => $instanceid]
        );
        if ((int)$latestid !== (int)$log->id) {
            throw new \moodle_exception('rollback_notice', 'local_examdates');
        }

        try {
            $course = get_course($log->courseid, false);
        } catch (\dml_missing_record_exception $e) {
            throw new \moodle_exception('error_coursedeleted', 'local_examdates');
        }

        $this->require_category_capability($course->category, 'local/examdates:manage');

        $activity = $DB->get_record($modulename, ['id' => $instanceid]);
        if (!$activity) {
            throw new \moodle_exception('error_activitydeleted', 'local_examdates');
        }

        $extra = [];
        if (!empty($log->extra_data)) {
            $decoded = json_decode($log->extra_data, true);
            if (is_array($decoded)) {
                $extra = $decoded;
            }
        }

        if ($modulename === 'assign') {
            $activity->allowsubmissionsfromdate = (int)$log->old_timeopen;
            $activity->duedate = (int)$log->old_timeclose;
            if (!empty($extra['old']) && is_array($extra['old'])) {
                if (array_key_exists('cutoffdate', $extra['old'])) {
                    $activity->cutoffdate = (int)$extra['old']['cutoffdate'];
                }
                if (array_key_exists('gradingduedate', $extra['old'])) {
                    $activity->gradingduedate = (int)$extra['old']['gradingduedate'];
                }
            }
        } else {
            $activity->timeopen = (int)$log->old_timeopen;
            $activity->timeclose = (int)$log->old_timeclose;
        }

        $activity->timemodified = time();
        $DB->update_record($modulename, $activity);

        $cm = get_coursemodule_from_instance($modulename, $activity->id, $course->id, false, MUST_EXIST);
        $this->update_activity_calendar($modulename, $activity, $cm->id, $course, $cm);

        $rollbackextra = [];
        if (!empty($extra)) {
            $rollbackextra = [
                'old' => $extra['new'] ?? [],
                'new' => $extra['old'] ?? [],
            ];
        }

        $this->log_change(
            $course,
            $modulename,
            $activity,
            $log->idnumber,
            $log->new_timeopen,
            $log->new_timeclose,
            $log->old_timeopen,
            $log->old_timeclose,
            $userid,
            'rollback_' . $log->batch_id,
            $rollbackextra
        );

        rebuild_course_cache($course->id, true);

        return true;
    }

    /**
     * Require a capability in a course category context.
     *
     * @param int $categoryid Category ID
     * @param string $capability Capability name
     * @param int|null $userid Check as this user rather than the current $USER.
     * @throws \required_capability_exception
     */
    private function require_category_capability($categoryid, $capability, $userid = null) {
        $context = \context_coursecat::instance($categoryid);
        require_capability($capability, $context, $userid);
    }

    /**
     * Delete log entries older than the configured retention period.
     */
    public function clean_old_logs() {
        global $DB;

        $retention = (int)get_config('local_examdates', 'log_retention_days');
        if ($retention <= 0) {
            return;
        }

        $expiry = time() - ($retention * DAYSECS);
        $DB->delete_records_select('local_examdates_log', 'timecreated < :expiry', ['expiry' => $expiry]);
    }
}
