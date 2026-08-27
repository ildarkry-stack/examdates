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
 * Handles bulk-updating quiz exam/resit dates for a course category, previewing
 * changes before they're applied, logging changes and rolling them back.
 */
class manager {
    /**
     * Get all courses in a category (and optionally its subcategories) that the
     * current user is allowed to see, per the given capability.
     *
     * @param int $categoryid Category ID
     * @param bool $includesub Include subcategories
     * @param string $capability Capability to check ('local/examdates:manage' or
     *                           'local/examdates:preview'). Defaults to manage
     *                           so existing callers keep their current behaviour.
     * @param int|null $userid Check as this user rather than the current $USER -
     *                         required when called from a background task, where
     *                         there is no "current" web user to fall back to.
     * @return array List of course records keyed by id
     */
    public function get_courses_by_category(
        $categoryid,
        $includesub = true,
        $capability = 'local/examdates:manage',
        $userid = null
    ) {
        global $DB;

        // Verify the user may access the starting category.
        $this->require_category_capability($categoryid, $capability, $userid);

        if (!$includesub) {
            return $DB->get_records('course', ['category' => $categoryid], 'fullname');
        }

        // Recursively collect subcategory IDs the user can access.
        $catids = [$categoryid];
        $allcategories = $DB->get_records('course_categories', [], '', 'id,parent');

        $added = true;
        while ($added) {
            $added = false;
            foreach ($allcategories as $cat) {
                if (in_array($cat->parent, $catids) && !in_array($cat->id, $catids)) {
                    try {
                        $this->require_category_capability($cat->id, $capability, $userid);
                        $catids[] = $cat->id;
                        $added = true;
                    } catch (\required_capability_exception $e) {
                        // No capability for this subcategory - skip it.
                        continue;
                    }
                }
            }
        }

        [$insql, $params] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
        return $DB->get_records_select('course', "category $insql", $params, 'fullname');
    }

    /**
     * Find a quiz course module by its idnumber within a course.
     *
     * @param int $courseid Course ID
     * @param string $idnumber Custom idnumber (e.g. exam, resit1, resit2 or any user-defined value)
     * @return \cm_info|\stdClass|null Course module-like object or null
     */
    private function get_quiz_by_idnumber($courseid, $idnumber) {
        global $DB;

        if (empty($idnumber)) {
            return null;
        }

        // Most reliable: direct lookup via course_modules.
        $sql = "SELECT cm.id AS cmid, cm.idnumber, cm.instance, cm.course,
                       q.id AS quizid, q.name AS quizname, q.timeopen, q.timeclose
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {quiz} q ON q.id = cm.instance
                 WHERE cm.course = :courseid
                   AND m.name = 'quiz'
                   AND cm.idnumber = :idnumber
                   AND cm.deletioninprogress = 0";

        $record = $DB->get_record_sql($sql, ['courseid' => $courseid, 'idnumber' => $idnumber]);

        if ($record) {
            $cm = new \stdClass();
            $cm->id = $record->cmid;
            $cm->idnumber = $record->idnumber;
            $cm->instance = $record->instance;
            $cm->course = $record->course;
            return $cm;
        }

        // Fallback: search via modinfo cache.
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_instances_of('quiz') as $cm) {
            if ($cm->idnumber === $idnumber) {
                return $cm;
            }
        }

        return null;
    }

    /**
     * Get current dates for preview.
     *
     * @param array $courses List of course records
     * @param object|null $idnumbers ID numbers for each quiz type
     * @return array Current dates data keyed by courseid
     */
    public function get_current_dates($courses, $idnumbers = null) {
        global $DB;

        $examid   = isset($idnumbers->exam_idnumber) ? $idnumbers->exam_idnumber : 'exam';
        $resit1id = isset($idnumbers->resit1_idnumber) ? $idnumbers->resit1_idnumber : 'resit1';
        $resit2id = isset($idnumbers->resit2_idnumber) ? $idnumbers->resit2_idnumber : 'resit2';

        $types = ['exam' => $examid, 'resit1' => $resit1id, 'resit2' => $resit2id];

        $result = [];
        foreach ($courses as $course) {
            $result[$course->id] = ['fullname' => $course->fullname];

            foreach ($types as $type => $idnumber) {
                $cm = $this->get_quiz_by_idnumber($course->id, $idnumber);
                $quiz = $cm ? $DB->get_record('quiz', ['id' => $cm->instance]) : false;

                if ($quiz) {
                    $result[$course->id][$type] = [
                        'timeopen'  => $quiz->timeopen,
                        'timeclose' => $quiz->timeclose,
                        'quizid'    => $quiz->id,
                        'quizname'  => $quiz->name,
                        'exists'    => true,
                    ];
                } else {
                    $result[$course->id][$type] = ['exists' => false];
                }
            }
        }

        return $result;
    }

    /**
     * Build preview data with statistics.
     *
     * @param array $courses List of course records
     * @param object $newdates New dates data
     * @return array ['preview' => ..., 'stats' => ...]
     */
    public function get_preview_data($courses, $newdates) {
        $current = $this->get_current_dates($courses, $newdates);

        $preview = [];
        $stats = [
            'total_courses'        => count($courses),
            'courses_with_changes' => 0,
            'total_updates'        => 0,
            'total_errors'         => 0,
            'exam_updates'         => 0,
            'resit1_updates'       => 0,
            'resit2_updates'       => 0,
            'exam_missing'         => 0,
            'resit1_missing'       => 0,
            'resit2_missing'       => 0,
        ];

        $types = ['exam', 'resit1', 'resit2'];

        foreach ($current as $courseid => $data) {
            $coursepreview = ['fullname' => $data['fullname'], 'has_changes' => false];

            foreach ($types as $type) {
                if (empty($newdates->{'update_' . $type})) {
                    continue;
                }

                if (!$data[$type]['exists']) {
                    $coursepreview[$type] = ['status' => 'missing'];
                    $stats[$type . '_missing']++;
                    $stats['total_errors']++;
                    continue;
                }

                $oldopen  = $data[$type]['timeopen'];
                $oldclose = $data[$type]['timeclose'];
                $newopen  = $newdates->{$type . 'open'};
                $newclose = $newdates->{$type . 'close'};

                if ($oldopen != $newopen || $oldclose != $newclose) {
                    $coursepreview[$type] = [
                        'status'    => 'will_change',
                        'old_open'  => $oldopen,
                        'old_close' => $oldclose,
                        'new_open'  => $newopen,
                        'new_close' => $newclose,
                    ];
                    $coursepreview['has_changes'] = true;
                    $stats[$type . '_updates']++;
                    $stats['total_updates']++;
                } else {
                    $coursepreview[$type] = [
                        'status'    => 'no_change',
                        'old_open'  => $oldopen,
                        'old_close' => $oldclose,
                    ];
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
        $summary .= get_string('found_quizzes', 'local_examdates') . ': '
            . ($stats['exam_updates'] + $stats['resit1_updates'] + $stats['resit2_updates']);
        $summary .= \html_writer::empty_tag('br');
        $summary .= get_string('errors', 'local_examdates') . ': ' . $stats['total_errors'];
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
            // Course name links to the course (opens in a new tab).
            $courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
            $row = [\html_writer::link(
                $courseurl,
                format_string($data['fullname']),
                ['target' => '_blank']
            )];

            foreach (['exam', 'resit1', 'resit2'] as $type) {
                $row[] = $this->render_preview_cell(isset($data[$type]) ? $data[$type] : null);
            }
            $table->data[] = $row;
        }

        return $summary . \html_writer::table($table);
    }

    /**
     * Render a single preview cell.
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
            return \html_writer::tag('span', get_string('notfound', 'local_examdates'), ['class' => 'text-danger']);
        }
        if ($cell['status'] === 'no_change') {
            $old = $this->format_date_range($cell['old_open'], $cell['old_close']);
            return \html_writer::tag(
                'span',
                $old . ' ' . \html_writer::tag('small', '(' . get_string('nochanges', 'local_examdates') . ')'),
                ['class' => 'text-muted']
            );
        }
        if ($cell['status'] === 'will_change') {
            $old = $this->format_date_range($cell['old_open'], $cell['old_close']);
            $new = $this->format_date_range($cell['new_open'], $cell['new_close']);
            return \html_writer::tag('span', $old, ['class' => 'text-warning'])
                . \html_writer::empty_tag('br')
                . get_string('arrow', 'local_examdates')
                . \html_writer::empty_tag('br')
                . \html_writer::tag('span', $new, ['class' => 'text-success font-weight-bold']);
        }
        return \html_writer::tag('span', '—', ['class' => 'text-muted']);
    }

    /**
     * Apply date updates to all matching quizzes.
     *
     * @param array $courses List of course records
     * @param object $newdates Prepared data
     * @param int $userid User performing the change
     * @return array ['updated' => [], 'errors' => [], 'skipped' => []]
     */
    public function apply_updates($courses, $newdates, $userid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        // Unique batch id. random_string() is preferred over uniqid() and stays
        // short enough that the 'rollback_' prefix still fits the char(40) column.
        $batchid = random_string(24);
        $result = ['updated' => [], 'errors' => [], 'skipped' => []];

        // Resolve which quiz types are being updated.
        $updatetypes = [];
        foreach (['exam', 'resit1', 'resit2'] as $type) {
            if (!empty($newdates->{'update_' . $type})) {
                $updatetypes[$type] = !empty($newdates->{$type . '_idnumber'})
                    ? $newdates->{$type . '_idnumber'}
                    : $type;

                // Server-side re-validation (the public form is bypassed by the confirm form).
                $open  = $newdates->{$type . 'open'};
                $close = $newdates->{$type . 'close'};
                if ($close <= $open) {
                    // Drop this type entirely; record one clear error.
                    unset($updatetypes[$type]);
                    $result['errors'][] = get_string('invalid_dates', 'local_examdates')
                        . ' (' . get_string($type, 'local_examdates') . ')';
                }
            }
        }

        foreach ($courses as $course) {
            // Defence in depth: re-check capability for this course's category.
            $categoryid = isset($course->category) ? $course->category : get_course($course->id)->category;
            try {
                $this->require_category_capability($categoryid, 'local/examdates:manage', $userid);
            } catch (\required_capability_exception $e) {
                $result['errors'][] = get_string('error_nopermission', 'local_examdates')
                    . ': ' . format_string($course->fullname);
                continue;
            }

            $coursechanged = false;

            foreach ($updatetypes as $type => $idnumber) {
                $cm = $this->get_quiz_by_idnumber($course->id, $idnumber);
                if (!$cm) {
                    $result['errors'][] = get_string('missing_idnumber', 'local_examdates', (object)[
                        'coursename' => format_string($course->fullname),
                        'idnumber'   => s($idnumber),
                    ]);
                    continue;
                }

                $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

                $oldopen  = $quiz->timeopen;
                $oldclose = $quiz->timeclose;
                $newopen  = $newdates->{$type . 'open'};
                $newclose = $newdates->{$type . 'close'};

                if ($oldopen == $newopen && $oldclose == $newclose) {
                    $result['skipped'][] = [
                        'coursename' => $course->fullname,
                        'quizname'   => $quiz->name,
                        'quiztype'   => $type,
                    ];
                    continue;
                }

                $quiz->timeopen = $newopen;
                $quiz->timeclose = $newclose;
                $quiz->timemodified = time();
                $DB->update_record('quiz', $quiz);

                // Keep calendar events in sync (raw DB update alone leaves them stale).
                if (function_exists('quiz_update_events')) {
                    quiz_update_events($quiz);
                }

                $this->log_change(
                    $course,
                    $quiz,
                    $idnumber,
                    $oldopen,
                    $oldclose,
                    $newopen,
                    $newclose,
                    $userid,
                    $batchid
                );

                $coursechanged = true;

                $result['updated'][] = [
                    'courseid'          => $course->id,
                    'coursename'        => $course->fullname,
                    'quizid'            => $quiz->id,
                    'quizname'          => $quiz->name,
                    'quiztype'          => $type,
                    'idnumber'          => $idnumber,
                    'old_timeopen_raw'  => $oldopen,
                    'old_timeclose_raw' => $oldclose,
                    'new_timeopen_raw'  => $newopen,
                    'new_timeclose_raw' => $newclose,
                    'old_dates'         => $this->format_date_range($oldopen, $oldclose),
                    'new_dates'         => $this->format_date_range($newopen, $newclose),
                ];
            }

            // Rebuild the course cache once per course, not once per quiz.
            if ($coursechanged) {
                rebuild_course_cache($course->id, true);
            }
        }

        if (!empty($result['updated'])) {
            $coursecount = count(array_unique(array_column($result['updated'], 'courseid')));
            $categoryid = isset($newdates->categoryid) ? (int)$newdates->categoryid : 0;
            $this->trigger_event($userid, $batchid, count($result['updated']), $categoryid, $coursecount);
        }

        return $result;
    }

    /**
     * Log a single change to the database.
     *
     * Honours the local_examdates/enable_logging setting (default: on).
     *
     * @param \stdClass $course Course record
     * @param \stdClass $quiz Quiz record (post-update values)
     * @param string $idnumber The quiz idnumber (exam/resit1/resit2)
     * @param int $oldopen Previous timeopen
     * @param int $oldclose Previous timeclose
     * @param int $newopen New timeopen
     * @param int $newclose New timeclose
     * @param int $userid User performing the change
     * @param string $batchid Batch identifier shared by one apply/rollback run
     */
    private function log_change(
        $course,
        $quiz,
        $idnumber,
        $oldopen,
        $oldclose,
        $newopen,
        $newclose,
        $userid,
        $batchid
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
        $record->quizid          = $quiz->id;
        $record->quiz_name       = $quiz->name;
        $record->idnumber        = $idnumber;
        $record->old_timeopen    = $oldopen ?: 0;
        $record->old_timeclose   = $oldclose ?: 0;
        $record->new_timeopen    = $newopen;
        $record->new_timeclose   = $newclose;
        // The rollback_change() method below prefixes its batch id with
        // 'rollback_' - reuse that same signal here so this column actually
        // distinguishes the two cases, instead of being hardcoded to one
        // constant value.
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
     * @param int $count Number of quiz-type slots changed
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
     * Roll back a specific change to its previous dates.
     *
     * @param int $logid Log record ID
     * @param int $userid User performing the rollback
     * @return bool True on success.
     * @throws \moodle_exception If the log entry, its course or its quiz can no longer be found.
     * @throws \required_capability_exception If the user cannot manage exam dates in that category.
     */
    public function rollback_change($logid, $userid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $log = $DB->get_record('local_examdates_log', ['id' => $logid]);
        if (!$log) {
            throw new \moodle_exception('error_lognotfound', 'local_examdates');
        }

        // Only the most recent change for this quiz may be rolled back - rolling
        // back an older entry would silently discard whatever changed after it.
        $latestid = $DB->get_field_sql(
            'SELECT MAX(id) FROM {local_examdates_log} WHERE quizid = :quizid',
            ['quizid' => $log->quizid]
        );
        if ((int)$latestid !== (int)$log->id) {
            throw new \moodle_exception('rollback_notice', 'local_examdates');
        }

        // The course may have been deleted since the change was logged (this is
        // exactly why course_fullname/quiz_name are denormalised on the log row).
        // get_course() throws rather than returning false, so this must be caught.
        try {
            $course = get_course($log->courseid, false);
        } catch (\dml_missing_record_exception $e) {
            throw new \moodle_exception('error_coursedeleted', 'local_examdates');
        }

        $this->require_category_capability($course->category, 'local/examdates:manage');

        $quiz = $DB->get_record('quiz', ['id' => $log->quizid]);
        if (!$quiz) {
            throw new \moodle_exception('error_quizdeleted', 'local_examdates');
        }

        $quiz->timeopen = $log->old_timeopen;
        $quiz->timeclose = $log->old_timeclose;
        $quiz->timemodified = time();
        $DB->update_record('quiz', $quiz);

        if (function_exists('quiz_update_events')) {
            quiz_update_events($quiz);
        }

        $this->log_change(
            $course,
            $quiz,
            $log->idnumber,
            $log->new_timeopen,
            $log->new_timeclose,
            $log->old_timeopen,
            $log->old_timeclose,
            $userid,
            'rollback_' . $log->batch_id
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
