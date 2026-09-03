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
 * Change-history page for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();

$PAGE->set_url('/local/examdates/history.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('history_title', 'local_examdates'));
$PAGE->set_heading(get_string('history_title', 'local_examdates'));

admin_externalpage_setup('local_examdates');

$manager = new \local_examdates\manager();

$baseurl = new moodle_url('/local/examdates/history.php');
$perpage = 50;

// Read filter values (flat scalars so they survive pagination and export links).
$page           = optional_param('page', 0, PARAM_INT);
$filtercourse   = optional_param('filtercourse', 0, PARAM_INT);
$filteruser     = optional_param('filteruser', 0, PARAM_INT);
$filteridnumber = optional_param('filteridnumber', '', PARAM_ALPHANUMEXT);
$filterfrom     = optional_param('filterfrom', 0, PARAM_INT);
$filterto       = optional_param('filterto', 0, PARAM_INT);

// Build the filter set passed to the manager.
$filters = [];
if ($filtercourse) {
    $filters['courseid'] = $filtercourse;
}
if ($filteruser) {
    $filters['userid'] = $filteruser;
}
if ($filteridnumber !== '') {
    $filters['idnumber'] = $filteridnumber;
}
if ($filterfrom) {
    $filters['from'] = $filterfrom;
}
if ($filterto) {
    // Make the "to" date inclusive of the whole selected day.
    $filters['to'] = $filterto + DAYSECS - 1;
}

// Flat params describing the active filter (without the page number).
$filterparams = [];
if ($filtercourse) {
    $filterparams['filtercourse'] = $filtercourse;
}
if ($filteruser) {
    $filterparams['filteruser'] = $filteruser;
}
if ($filteridnumber !== '') {
    $filterparams['filteridnumber'] = $filteridnumber;
}
if ($filterfrom) {
    $filterparams['filterfrom'] = $filterfrom;
}
if ($filterto) {
    $filterparams['filterto'] = $filterto;
}
$hasfilters = !empty($filterparams);

// Build option lists from the values that actually appear in the log.
$courseoptions = [];
$courserows = $DB->get_records_sql(
    'SELECT courseid, MAX(course_fullname) AS name
       FROM {local_examdates_log}
   GROUP BY courseid'
);
foreach ($courserows as $row) {
    $courseoptions[$row->courseid] = $row->name !== null && $row->name !== ''
        ? $row->name
        : '#' . $row->courseid;
}
asort($courseoptions);

$useroptions = [];
$userids = $DB->get_fieldset_sql('SELECT DISTINCT userid FROM {local_examdates_log}');
if ($userids) {
    $userrecords = $DB->get_records_list('user', 'id', $userids);
    foreach ($userids as $uid) {
        $useroptions[$uid] = isset($userrecords[$uid]) ? fullname($userrecords[$uid]) : '#' . $uid;
    }
}
asort($useroptions);

$idoptions = [];
$idnumbers = $DB->get_fieldset_sql(
    'SELECT DISTINCT idnumber FROM {local_examdates_log} ORDER BY idnumber'
);
foreach ($idnumbers as $idn) {
    $idoptions[$idn] = $idn;
}

// Filter form.
$filterform = new \local_examdates\form\history_filter_form($baseurl, [
    'courses'   => $courseoptions,
    'users'     => $useroptions,
    'idnumbers' => $idoptions,
    'expanded'  => $hasfilters,
]);

// On submit, redirect to a canonical URL carrying flat params (page reset to 0).
if ($data = $filterform->get_data()) {
    $redirectparams = [];
    if (!empty($data->filtercourse)) {
        $redirectparams['filtercourse'] = $data->filtercourse;
    }
    if (!empty($data->filteruser)) {
        $redirectparams['filteruser'] = $data->filteruser;
    }
    if (!empty($data->filteridnumber)) {
        $redirectparams['filteridnumber'] = $data->filteridnumber;
    }
    if (!empty($data->filterfrom)) {
        $redirectparams['filterfrom'] = $data->filterfrom;
    }
    if (!empty($data->filterto)) {
        $redirectparams['filterto'] = $data->filterto;
    }
    redirect(new moodle_url('/local/examdates/history.php', $redirectparams));
}

// Pre-fill the form with the current selection.
$filterform->set_data([
    'filtercourse'   => $filtercourse,
    'filteruser'     => $filteruser,
    'filteridnumber' => $filteridnumber,
    'filterfrom'     => $filterfrom,
    'filterto'       => $filterto,
]);

$datetimeformat = get_string('strftimedatetime', 'langconfig');

// Rollback a specific change (two-step confirm, must run before any output).
$rollbackid = optional_param('rollback', 0, PARAM_INT);
if ($rollbackid) {
    require_sesskey();

    $returnurl = new moodle_url($baseurl, $filterparams);

    // Fetch display names up front from the log row's own denormalised
    // activity/course names. This still gives a meaningful message if the
    // course or activity was deleted after the change.
    $rollbacklog = $DB->get_record('local_examdates_log', ['id' => $rollbackid]);
    $rollbacka = (object)[
        'activityname' => $rollbacklog ? ($rollbacklog->activity_name ?: $rollbacklog->quiz_name) : '',
        'coursename' => $rollbacklog ? $rollbacklog->course_fullname : '',
    ];

    if (!optional_param('confirm', 0, PARAM_BOOL)) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('rollback_confirm', 'local_examdates'),
            new moodle_url($baseurl, $filterparams + [
                'rollback' => $rollbackid,
                'confirm'  => 1,
                'sesskey'  => sesskey(),
            ]),
            $returnurl
        );
        echo $OUTPUT->footer();
        exit;
    }

    try {
        $manager->rollback_change($rollbackid, $USER->id);
        redirect(
            $returnurl,
            get_string('rollback_success', 'local_examdates', $rollbacka),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\required_capability_exception $e) {
        redirect(
            $returnurl,
            get_string('error_nopermission', 'local_examdates'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    } catch (\moodle_exception $e) {
        redirect(
            $returnurl,
            get_string('rollback_error', 'local_examdates', $rollbacka) . ': ' . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// CSV export must run before any page output.
if (optional_param('export', '', PARAM_ALPHA) === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');

    $all = $manager->get_history($filters, 0, 0); // 0 perpage = all matching records.

    $csv = new csv_export_writer();
    $csv->set_filename('examdates_history_' . date('Ymd_His'));
    $csv->add_data([
        get_string('changed_at', 'local_examdates'),
        get_string('changed_by', 'local_examdates'),
        get_string('course', 'local_examdates'),
        get_string('activity', 'local_examdates'),
        get_string('idnumber', 'local_examdates'),
        get_string('old_dates', 'local_examdates'),
        get_string('new_dates', 'local_examdates'),
    ]);

    $exportusers = [];
    if ($all['records']) {
        $exportuserids = array_unique(array_map(function ($r) {
            return $r->userid;
        }, $all['records']));
        $exportusers = $DB->get_records_list('user', 'id', $exportuserids);
    }

    foreach ($all['records'] as $record) {
        $user = isset($exportusers[$record->userid]) ? $exportusers[$record->userid] : null;
        $modulename = !empty($record->modulename) ? $record->modulename : 'quiz';
        $modulelabel = get_string($modulename === 'quiz' ? 'quiz' : 'assignment', 'local_examdates');
        $activityname = format_string($record->activity_name ?: $record->quiz_name);
        $csv->add_data([
            userdate($record->timecreated, $datetimeformat),
            $user ? fullname($user) : '-',
            format_string($record->course_fullname),
            $activityname . ' [' . $modulelabel . ']',
            $record->idnumber,
            $manager->format_date_range($record->old_timeopen, $record->old_timeclose),
            $manager->format_date_range($record->new_timeopen, $record->new_timeclose),
        ]);
    }

    $csv->download_file(); // Sends the file and exits.
}

echo $OUTPUT->header();

// Filter form + reset link.
$filterform->display();
if ($hasfilters) {
    echo html_writer::link(
        $baseurl,
        get_string('reset_filters', 'local_examdates'),
        ['class' => 'btn btn-secondary mb-3']
    );
}

$history = $manager->get_history($filters, $page, $perpage);

if (empty($history['records'])) {
    echo $OUTPUT->notification(get_string('history_empty', 'local_examdates'), 'info');
} else {
    // Export current selection.
    $exporturl = new moodle_url(
        '/local/examdates/history.php',
        $filterparams + ['export' => 'csv']
    );
    echo html_writer::div(
        html_writer::link(
            $exporturl,
            get_string('export_csv', 'local_examdates'),
            ['class' => 'btn btn-secondary']
        ),
        'mb-2'
    );

    echo html_writer::tag(
        'p',
        get_string('records_total', 'local_examdates', $history['total']),
        ['class' => 'text-muted']
    );

    echo html_writer::tag(
        'p',
        get_string('rollback_notice', 'local_examdates'),
        ['class' => 'text-muted small font-italic']
    );

    $table = new html_table();
    $table->head = [
        get_string('changed_at', 'local_examdates'),
        get_string('changed_by', 'local_examdates'),
        get_string('course', 'local_examdates'),
        get_string('activity', 'local_examdates'),
        get_string('old_dates', 'local_examdates'),
        get_string('new_dates', 'local_examdates'),
        get_string('actions', 'local_examdates'),
    ];
    $table->data = [];

    // Batch-load everything referenced on this page. Activity records are
    // grouped by module so Quiz and Assignment history remains N+1-safe.
    $pageuserids = array_unique(array_map(function ($r) {
        return $r->userid;
    }, $history['records']));
    $pageusers = $pageuserids ? $DB->get_records_list('user', 'id', $pageuserids) : [];

    $pagecourseids = array_unique(array_map(function ($r) {
        return $r->courseid;
    }, $history['records']));
    $pagecourses = $pagecourseids ? $DB->get_records_list('course', 'id', $pagecourseids) : [];

    $activityids = ['quiz' => [], 'assign' => []];
    foreach ($history['records'] as $record) {
        $modulename = !empty($record->modulename) ? $record->modulename : 'quiz';
        $instanceid = !empty($record->instanceid) ? (int)$record->instanceid : (int)$record->quizid;
        if (isset($activityids[$modulename]) && $instanceid > 0) {
            $activityids[$modulename][$instanceid] = $instanceid;
        }
    }

    $pageactivities = ['quiz' => [], 'assign' => []];
    $latestidbyactivity = [];
    foreach ($activityids as $modulename => $instanceids) {
        if (!$instanceids) {
            continue;
        }

        $pageactivities[$modulename] = $DB->get_records_list(
            $modulename,
            'id',
            array_values($instanceids)
        );

        [$insql, $inparams] = $DB->get_in_or_equal(
            array_values($instanceids),
            SQL_PARAMS_NAMED,
            'instance'
        );
        $inparams['modulename'] = $modulename;
        $latestrows = $DB->get_records_sql(
            "SELECT instanceid, MAX(id) AS maxid
               FROM {local_examdates_log}
              WHERE modulename = :modulename AND instanceid $insql
           GROUP BY instanceid",
            $inparams
        );
        foreach ($latestrows as $row) {
            $latestidbyactivity[$modulename . ':' . $row->instanceid] = $row->maxid;
        }
    }

    foreach ($history['records'] as $record) {
        $user = isset($pageusers[$record->userid]) ? $pageusers[$record->userid] : null;
        $course = isset($pagecourses[$record->courseid]) ? $pagecourses[$record->courseid] : null;
        $modulename = !empty($record->modulename) ? $record->modulename : 'quiz';
        $instanceid = !empty($record->instanceid) ? (int)$record->instanceid : (int)$record->quizid;
        $activity = $pageactivities[$modulename][$instanceid] ?? null;

        $olddates = $manager->format_date_range($record->old_timeopen, $record->old_timeclose);
        $newdates = $manager->format_date_range($record->new_timeopen, $record->new_timeclose);

        // Fall back to a live lookup if the denormalised name is empty; if the
        // course has since been deleted, say so instead of linking to nothing.
        $coursename = $record->course_fullname;
        if (empty($coursename) && $course) {
            $coursename = $course->fullname;
        }

        if (empty($coursename)) {
            $coursecell = html_writer::tag(
                'span',
                get_string('course_deleted', 'local_examdates'),
                ['class' => 'text-muted font-italic']
            );
        } else {
            $courseurl = new moodle_url('/course/view.php', ['id' => $record->courseid]);
            $coursecell = html_writer::link($courseurl, format_string($coursename), ['target' => '_blank']);
        }

        $activityname = $record->activity_name ?: $record->quiz_name;
        if (empty($activityname)) {
            $activityname = $activity ? $activity->name : $record->idnumber;
        }

        // Rollback is offered only for the latest change of the exact activity
        // instance and only while both the course and activity still exist.
        $activitykey = $modulename . ':' . $instanceid;
        $islatest = isset($latestidbyactivity[$activitykey])
            && $latestidbyactivity[$activitykey] == $record->id;

        $canrollback = false;
        if ($islatest && $course && $activity && !empty($course->category)) {
            $canrollback = has_capability('local/examdates:manage', \context_coursecat::instance($course->category));
        }

        if ($canrollback) {
            $rollbackurl = new moodle_url($baseurl, $filterparams + [
                'rollback' => $record->id,
                'sesskey'  => sesskey(),
            ]);
            $actioncell = html_writer::link(
                $rollbackurl,
                get_string('rollback', 'local_examdates'),
                ['class' => 'btn btn-sm btn-outline-danger']
            );
        } else {
            $actioncell = '';
        }

        $table->data[] = [
            userdate($record->timecreated, $datetimeformat),
            $user ? fullname($user) : '-',
            $coursecell,
            format_string($activityname) . ' ['
                . get_string($modulename === 'quiz' ? 'quiz' : 'assignment', 'local_examdates')
                . '] (' . s($record->idnumber) . ')',
            $olddates,
            $newdates,
            $actioncell,
        ];
    }

    echo html_writer::table($table);

    // Pagination (filter params carried via the base URL).
    $pagingurl = new moodle_url('/local/examdates/history.php', $filterparams);
    echo $OUTPUT->paging_bar($history['total'], $page, $perpage, $pagingurl);
}

echo html_writer::link(
    new moodle_url('/local/examdates/index.php'),
    get_string('back', 'local_examdates'),
    ['class' => 'btn btn-secondary mt-3']
);

echo $OUTPUT->footer();
