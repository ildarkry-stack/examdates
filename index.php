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

    // Deliberately fast, bounded checks only - resolving the course list
    // (get_courses_by_category()) is left to the background task, so this
    // stays quick even for a category with thousands of courses. The task
    // re-checks capability per course anyway (defence in depth); this is
    // just for immediate feedback on the two most common mistakes.
    $errors = [];

    if (!has_capability('local/examdates:manage', context_coursecat::instance($preparedata->categoryid))) {
        $errors[] = get_string('error_nopermission', 'local_examdates');
    }

    if (
        empty($preparedata->update_exam) && empty($preparedata->update_resit1)
            && empty($preparedata->update_resit2)
    ) {
        $errors[] = get_string('select_at_least_one', 'local_examdates');
    }

    foreach (['exam', 'resit1', 'resit2'] as $type) {
        if (
            !empty($preparedata->{'update_' . $type})
                && $preparedata->{$type . 'close'} <= $preparedata->{$type . 'open'}
        ) {
            $errors[] = get_string('invalid_dates', 'local_examdates')
                . ' (' . get_string($type, 'local_examdates') . ')';
        }
    }

    if ($errors) {
        foreach ($errors as $error) {
            echo $OUTPUT->notification($error, 'error');
        }
        echo html_writer::link(
            new moodle_url('/local/examdates/index.php'),
            get_string('back', 'local_examdates'),
            ['class' => 'btn btn-secondary mt-3']
        );
        echo $OUTPUT->footer();
        exit;
    }

    // Queue the actual bulk update as a background task rather than running
    // it inline: a large category can take far longer than the request's
    // execution-time limit, and a mid-batch failure in a synchronous
    // request leaves an unrecoverable, half-applied state with nothing
    // shown to the person who clicked Apply.
    $preparedata->userid = $USER->id;

    $task = new \local_examdates\task\apply_updates_task();
    $task->set_userid($USER->id);
    $task->set_custom_data($preparedata);
    \core\task\manager::queue_adhoc_task($task);

    echo $OUTPUT->notification(get_string('apply_queued', 'local_examdates'), 'success');

    echo html_writer::link(
        new moodle_url('/local/examdates/history.php'),
        get_string('view_history', 'local_examdates'),
        ['class' => 'btn btn-primary mt-3']
    );
    echo ' ';
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
 *
 * Preview rows are paginated. The tiny form state is kept in the current
 * user's Moodle session behind a random token, so paging links do not need to
 * expose all selected dates/idnumbers in the URL and do not re-run the form.
 */
if (!isset($SESSION->local_examdates_preview_states) || !is_array($SESSION->local_examdates_preview_states)) {
    $SESSION->local_examdates_preview_states = [];
}

// Expire stale preview state so repeated previews cannot grow a session forever.
$previewcutoff = time() - HOURSECS;
foreach ($SESSION->local_examdates_preview_states as $token => $state) {
    if (empty($state['created']) || $state['created'] < $previewcutoff) {
        unset($SESSION->local_examdates_preview_states[$token]);
    }
}

// A category may be preset via the URL (e.g. the "manage" link from
// preview.php); it's only a default for the dropdown, not an access check.
$presetcategoryid = optional_param('categoryid', 0, PARAM_INT);
$previewtoken = optional_param('previewtoken', '', PARAM_ALPHANUM);
$page = max(0, optional_param('page', 0, PARAM_INT));
$mform = new \local_examdates\form\examdates_form(null, ['presetcategoryid' => $presetcategoryid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/examdates/index.php'));
}

$preparedata = null;
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

    $previewtoken = random_string(32);
    $SESSION->local_examdates_preview_states[$previewtoken] = [
        'created' => time(),
        'mode' => 'manage',
        'categoryid' => (int)$preparedata->categoryid,
        'data' => $preparedata,
    ];
    $page = 0;
} elseif ($previewtoken !== '') {
    $state = $SESSION->local_examdates_preview_states[$previewtoken] ?? null;
    if ($state && ($state['mode'] ?? '') === 'manage' && !empty($state['data'])) {
        $preparedata = $state['data'];
    } else {
        echo $OUTPUT->notification(get_string('preview_expired', 'local_examdates'), 'warning');
    }
}

if ($preparedata) {
    $totalcourses = $manager->count_courses_by_category(
        $preparedata->categoryid,
        !empty($preparedata->include_sub)
    );

    if ($totalcourses === 0) {
        echo $OUTPUT->notification(get_string('no_courses_found', 'local_examdates'), 'warning');
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }

    $perpage = \local_examdates\manager::PREVIEW_PAGE_SIZE;
    $lastpage = max(0, (int)ceil($totalcourses / $perpage) - 1);
    $page = min($page, $lastpage);
    $courses = $manager->get_courses_by_category(
        $preparedata->categoryid,
        !empty($preparedata->include_sub),
        'local/examdates:manage',
        null,
        $page * $perpage,
        $perpage
    );

    $previewdata = $manager->get_preview_data($courses, $preparedata);
    $stats = $previewdata['stats'];

    echo $OUTPUT->notification(
        get_string('preview_page_stats_message', 'local_examdates', (object)[
            'tests'   => $stats['total_updates'],
            'courses' => $stats['courses_with_changes'],
            'errors'  => $stats['total_errors'],
            'shown'   => count($courses),
            'total'   => $totalcourses,
        ]),
        'info'
    );

    $pagingurl = new moodle_url('/local/examdates/index.php', ['previewtoken' => $previewtoken]);
    echo $OUTPUT->paging_bar($totalcourses, $page, $perpage, $pagingurl);

    // Only the current page is materialised and rendered.
    echo $manager->render_preview_table($previewdata);
    echo $OUTPUT->paging_bar($totalcourses, $page, $perpage, $pagingurl);

    // With more than one page, the current page cannot prove that the entire
    // scope has no changes. Applying still processes every course in bounded
    // background chunks, so keep the confirmation available for the full scope.
    $showconfirm = ($stats['total_updates'] > 0 || $totalcourses > $perpage);
    if ($showconfirm) {
        echo html_writer::start_div(
            'mt-4 p-3 border rounded',
            ['style' => 'background-color:#e8f5e9;border-color:#4caf50 !important;']
        );

        echo html_writer::tag(
            'h4',
            get_string('confirm_apply_title', 'local_examdates'),
            ['class' => 'text-success']
        );

        $confirmtext = $totalcourses > $perpage
            ? get_string('confirm_apply_text_paged', 'local_examdates', (object)['total' => $totalcourses])
            : get_string('confirm_apply_text', 'local_examdates', (object)[
                'tests' => $stats['total_updates'],
                'courses' => $stats['courses_with_changes'],
            ]);

        echo html_writer::tag(
            'p',
            $confirmtext,
            ['class' => 'font-weight-bold', 'style' => 'font-size:1.1rem;']
        );

        $confirmform = new \local_examdates\form\confirm_form(null, [
            'data' => $preparedata,
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
