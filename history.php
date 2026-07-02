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
   GROUP BY courseid');
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
    'SELECT DISTINCT idnumber FROM {local_examdates_log} ORDER BY idnumber');
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
        get_string('quiz', 'local_examdates'),
        get_string('idnumber', 'local_examdates'),
        get_string('old_dates', 'local_examdates'),
        get_string('new_dates', 'local_examdates'),
    ]);

    $exportusers = [];
    if ($all['records']) {
        $exportuserids = array_unique(array_map(function($r) {
            return $r->userid;
        }, $all['records']));
        $exportusers = $DB->get_records_list('user', 'id', $exportuserids);
    }

    foreach ($all['records'] as $record) {
        $user = isset($exportusers[$record->userid]) ? $exportusers[$record->userid] : null;
        $csv->add_data([
            userdate($record->timecreated, $datetimeformat),
            $user ? fullname($user) : '-',
            $record->course_fullname,
            $record->quiz_name,
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
    $exporturl = new moodle_url('/local/examdates/history.php',
        $filterparams + ['export' => 'csv']);
    echo html_writer::div(
        html_writer::link($exporturl, get_string('export_csv', 'local_examdates'),
            ['class' => 'btn btn-secondary']),
        'mb-2'
    );

    echo html_writer::tag('p',
        get_string('records_total', 'local_examdates', $history['total']),
        ['class' => 'text-muted']);

    $table = new html_table();
    $table->head = [
        get_string('changed_at', 'local_examdates'),
        get_string('changed_by', 'local_examdates'),
        get_string('course', 'local_examdates'),
        get_string('quiz', 'local_examdates'),
        get_string('old_dates', 'local_examdates'),
        get_string('new_dates', 'local_examdates'),
    ];
    $table->data = [];

    // Batch-load the users referenced on this page (avoids N+1 queries).
    $pageuserids = array_unique(array_map(function($r) {
        return $r->userid;
    }, $history['records']));
    $pageusers = $pageuserids ? $DB->get_records_list('user', 'id', $pageuserids) : [];

    foreach ($history['records'] as $record) {
        $user = isset($pageusers[$record->userid]) ? $pageusers[$record->userid] : null;

        $olddates = $manager->format_date_range($record->old_timeopen, $record->old_timeclose);
        $newdates = $manager->format_date_range($record->new_timeopen, $record->new_timeclose);

        // Fall back to a live lookup if the denormalised name is empty.
        $coursename = $record->course_fullname;
        if (empty($coursename) && ($course = get_course($record->courseid, false))) {
            $coursename = $course->fullname;
        }

        $quizname = $record->quiz_name;
        if (empty($quizname)) {
            $quiz = $DB->get_record('quiz', ['id' => $record->quizid]);
            $quizname = $quiz ? $quiz->name : $record->idnumber;
        }

        $courseurl = new moodle_url('/course/view.php', ['id' => $record->courseid]);

        $table->data[] = [
            userdate($record->timecreated, $datetimeformat),
            $user ? fullname($user) : '-',
            html_writer::link($courseurl, format_string($coursename), ['target' => '_blank']),
            format_string($quizname) . ' (' . s($record->idnumber) . ')',
            $olddates,
            $newdates,
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
