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
 * Read-only exam dates preview for a single category.
 *
 * Unlike index.php (Site administration, requires local/examdates:manage
 * site-wide), this page is reached from the category's own admin menu and
 * only requires local/examdates:preview (or manage) in that category's own
 * context - so a teacher/editingteacher with no site-admin access at all can
 * still see what a date change would look like before asking a manager to
 * apply it.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$categoryid = required_param('categoryid', PARAM_INT);
$category = core_course_category::get($categoryid, MUST_EXIST, true);
$context = context_coursecat::instance($categoryid);

$canmanage = has_capability('local/examdates:manage', $context);
if (!$canmanage && !has_capability('local/examdates:preview', $context)) {
    throw new \moodle_exception('error_nopermission_preview', 'local_examdates');
}

$PAGE->set_url('/local/examdates/preview.php', ['categoryid' => $categoryid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_examdates'));
$PAGE->set_heading(get_string('preview_heading', 'local_examdates', $category->get_formatted_name()));

echo $OUTPUT->header();

if (!$canmanage) {
    echo $OUTPUT->notification(get_string('preview_readonly_notice', 'local_examdates'), 'info');
}

$manager = new \local_examdates\manager();
if (!isset($SESSION->local_examdates_preview_states) || !is_array($SESSION->local_examdates_preview_states)) {
    $SESSION->local_examdates_preview_states = [];
}

$previewcutoff = time() - HOURSECS;
foreach ($SESSION->local_examdates_preview_states as $token => $state) {
    if (empty($state['created']) || $state['created'] < $previewcutoff) {
        unset($SESSION->local_examdates_preview_states[$token]);
    }
}

$previewtoken = optional_param('previewtoken', '', PARAM_ALPHANUM);
$page = max(0, optional_param('page', 0, PARAM_INT));

$mform = new \local_examdates\form\examdates_form(null, [
    'fixedcategoryid'   => $categoryid,
    'fixedcategoryname' => $category->get_formatted_name(),
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/examdates/preview.php', ['categoryid' => $categoryid]));
}

$preparedata = null;
if ($data = $mform->get_data()) {
    $preparedata = new stdClass();
    $preparedata->categoryid = $categoryid;
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
        'mode' => 'preview',
        'categoryid' => $categoryid,
        'data' => $preparedata,
    ];
    $page = 0;
} else if ($previewtoken !== '') {
    $state = $SESSION->local_examdates_preview_states[$previewtoken] ?? null;
    if (
        $state
        && ($state['mode'] ?? '') === 'preview'
        && (int)($state['categoryid'] ?? 0) === $categoryid
        && !empty($state['data'])
    ) {
        $preparedata = $state['data'];
    } else {
        echo $OUTPUT->notification(get_string('preview_expired', 'local_examdates'), 'warning');
    }
}

if ($preparedata) {
    // Checked against 'preview' rather than 'manage', so preview-only users and
    // managers see the same bounded result set.
    $totalcourses = $manager->count_courses_by_category(
        $categoryid,
        !empty($preparedata->include_sub),
        'local/examdates:preview'
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
        $categoryid,
        !empty($preparedata->include_sub),
        'local/examdates:preview',
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

    $pagingurl = new moodle_url('/local/examdates/preview.php', [
        'categoryid' => $categoryid,
        'previewtoken' => $previewtoken,
    ]);
    echo $OUTPUT->paging_bar($totalcourses, $page, $perpage, $pagingurl);
    echo $manager->render_preview_table($previewdata);
    echo $OUTPUT->paging_bar($totalcourses, $page, $perpage, $pagingurl);

    if ($canmanage) {
        // A manager can go straight on to apply the change, category preset.
        echo html_writer::div(
            html_writer::link(
                new moodle_url('/local/examdates/index.php', ['categoryid' => $categoryid]),
                get_string('go_to_manage', 'local_examdates'),
                ['class' => 'btn btn-primary mt-3']
            ),
            'mt-3'
        );
    } else {
        echo $OUTPUT->notification(get_string('preview_readonly_notice', 'local_examdates'), 'info');
    }

    echo html_writer::link(
        new moodle_url('/local/examdates/preview.php', ['categoryid' => $categoryid]),
        get_string('back', 'local_examdates'),
        ['class' => 'btn btn-secondary mt-3']
    );
} else {
    $mform->display();
}

echo $OUTPUT->footer();
