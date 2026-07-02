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
 * Management page for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();

$PAGE->set_url('/local/examdates/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_examdates'));
$PAGE->set_heading(get_string('pluginname', 'local_examdates'));

// Enforces 'local/examdates:manage' at system context and sets up the admin page.
admin_externalpage_setup('local_examdates');

echo $OUTPUT->header();

$manager = new \local_examdates\manager();

if (optional_param('cancel', 0, PARAM_BOOL)) {
    redirect(new moodle_url('/local/examdates/index.php'));
}

$action = optional_param('action', '', PARAM_ALPHA);

/*
 * APPLY (submitted from confirm_form).
 */
if ($action === 'apply') {

    // The confirm form's Cancel button must not apply anything - bail out first.
    if (optional_param('cancelbutton', '', PARAM_RAW) !== '') {
        redirect(new moodle_url('/local/examdates/index.php'));
    }

    require_sesskey();

    $preparedata = new stdClass();
    $preparedata->categoryid  = required_param('categoryid', PARAM_INT);
    $preparedata->include_sub = optional_param('include_sub', 0, PARAM_INT);

    $preparedata->update_exam   = optional_param('update_exam', 0, PARAM_INT);
    $preparedata->update_resit1 = optional_param('update_resit1', 0, PARAM_INT);
    $preparedata->update_resit2 = optional_param('update_resit2', 0, PARAM_INT);

    $preparedata->exam_idnumber   = optional_param('exam_idnumber', 'exam', PARAM_ALPHANUMEXT);
    $preparedata->resit1_idnumber = optional_param('resit1_idnumber', 'resit1', PARAM_ALPHANUMEXT);
    $preparedata->resit2_idnumber = optional_param('resit2_idnumber', 'resit2', PARAM_ALPHANUMEXT);

    foreach (['exam', 'resit1', 'resit2'] as $type) {
        if (!empty($preparedata->{'update_' . $type})) {
            $preparedata->{$type . 'open'}  = optional_param($type . 'open', 0, PARAM_INT);
            $preparedata->{$type . 'close'} = optional_param($type . 'close', 0, PARAM_INT);
        }
    }

    $courses = $manager->get_courses_by_category(
        $preparedata->categoryid,
        !empty($preparedata->include_sub)
    );

    $result = $manager->apply_updates($courses, $preparedata, $USER->id);

    if (!empty($result['errors'])) {
        echo $OUTPUT->notification(
            get_string('errors', 'local_examdates') . ': ' . count($result['errors']),
            'warning'
        );
        foreach ($result['errors'] as $error) {
            echo $OUTPUT->notification($error, 'error');
        }
    }

    if (!empty($result['updated'])) {
        $uniquecourses = count(array_unique(array_column($result['updated'], 'courseid')));

        echo $OUTPUT->notification(
            get_string('changes_applied_detailed', 'local_examdates', (object)[
                'tests'   => count($result['updated']),
                'courses' => $uniquecourses,
            ]),
            'success'
        );

        echo $manager->render_summary_table($result['updated']);
    }

    if (empty($result['updated']) && empty($result['errors'])) {
        echo $OUTPUT->notification(get_string('no_changes_made', 'local_examdates'), 'info');
    }

    echo html_writer::link(
        new moodle_url('/local/examdates/index.php'),
        get_string('back', 'local_examdates'),
        ['class' => 'btn btn-secondary mt-3']
    );

    echo $OUTPUT->footer();
    exit;
}

/*
 * PREVIEW (main form).
 */
$mform = new \local_examdates\form\examdates_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/examdates/index.php'));
}

if ($data = $mform->get_data()) {

    $preparedata = new stdClass();
    $preparedata->categoryid  = $data->categoryid;
    $preparedata->include_sub = !empty($data->include_sub);

    $preparedata->update_exam   = !empty($data->update_exam);
    $preparedata->update_resit1 = !empty($data->update_resit1);
    $preparedata->update_resit2 = !empty($data->update_resit2);

    foreach (['exam', 'resit1', 'resit2'] as $type) {
        $idfield = $type . '_idnumber';
        $preparedata->{$idfield} = !empty($data->{$idfield}) ? trim($data->{$idfield}) : $type;

        if (!empty($data->{'update_' . $type})) {
            $preparedata->{$type . 'open'}  = $data->{$type . 'open'};
            $preparedata->{$type . 'close'} = $data->{$type . 'close'};
        }
    }

    $courses = $manager->get_courses_by_category($data->categoryid, !empty($data->include_sub));

    if (empty($courses)) {
        echo $OUTPUT->notification(get_string('no_courses_found', 'local_examdates'), 'warning');
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }

    $previewdata = $manager->get_preview_data($courses, $preparedata);
    $stats = $previewdata['stats'];

    echo $OUTPUT->notification(
        get_string('preview_stats_message', 'local_examdates', (object)[
            'tests'   => $stats['total_updates'],
            'courses' => $stats['courses_with_changes'],
            'errors'  => $stats['total_errors'],
        ]),
        'info'
    );

    // Show the detailed before/after table.
    echo $manager->render_preview_table($previewdata);

    if ($stats['total_updates'] > 0) {

        echo html_writer::start_div('mt-4 p-3 border rounded',
            ['style' => 'background-color:#e8f5e9;border-color:#4caf50 !important;']);

        echo html_writer::tag('h4',
            get_string('confirm_apply_title', 'local_examdates'),
            ['class' => 'text-success']);

        echo html_writer::tag('p',
            get_string('confirm_apply_text', 'local_examdates', (object)[
                'tests'   => $stats['total_updates'],
                'courses' => $stats['courses_with_changes'],
            ]),
            ['class' => 'font-weight-bold', 'style' => 'font-size:1.1rem;']);

        $confirmform = new \local_examdates\form\confirm_form(null, [
            'courses' => $courses,
            'data'    => $preparedata,
            'stats'   => $stats,
        ]);
        $confirmform->display();

        echo html_writer::end_div();

    } else {
        echo $OUTPUT->notification(get_string('no_changes_made', 'local_examdates'), 'info');
        echo html_writer::link(
            new moodle_url('/local/examdates/index.php'),
            get_string('back', 'local_examdates'),
            ['class' => 'btn btn-secondary mt-3']
        );
    }

} else {
    $mform->display();
}

echo html_writer::empty_tag('hr');

echo html_writer::link(
    new moodle_url('/local/examdates/history.php'),
    get_string('view_history', 'local_examdates'),
    ['class' => 'btn btn-secondary']
);

echo $OUTPUT->footer();