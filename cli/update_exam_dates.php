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
 * CLI tool to bulk-update Quiz and Assignment dates for a course category.
 *
 * Mirrors what the web UI (index.php) does, so it re-uses the same manager
 * methods (and therefore the same logging, calendar sync and validation).
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help'        => false,
        'categoryid'  => 0,
        'includesub'  => 1,
        'examopen'    => '',
        'examclose'   => '',
        'examid'      => 'exam',
        'examassignid' => '',
        'resit1open'  => '',
        'resit1close' => '',
        'resit1id'    => 'resit1',
        'resit1assignid' => '',
        'resit2open'  => '',
        'resit2close' => '',
        'resit2id'    => 'resit2',
        'resit2assignid' => '',
        'dryrun'      => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode(PHP_EOL . '  ', $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (!empty($options['help']) || empty($options['categoryid'])) {
    cli_writeln(get_string('cli_usage', 'local_examdates'));
    exit(empty($options['help']) ? 1 : 0);
}

$categoryid = (int)$options['categoryid'];
if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
    cli_error(get_string('cli_error_category', 'local_examdates', $categoryid));
}

// Run as the primary admin: this is a trusted, server-side operation rather
// than an action taken by a specific logged-in web user.
//
// cron_setup_user() (deprecated since Moodle 4.2) still worked on 4.5 - it
// internally called \core\cron::setup_user() - but on Moodle 5.0+ it was
// reduced to a no-op stub that only emits a deprecation notice and does
// nothing else, silently leaving $USER unset and every capability check
// below failing. Call the real implementation directly instead.
\core\cron::setup_user();
require_capability('local/examdates:bulkupdate', context_system::instance());

$dryrun = (bool)$options['dryrun'];

$preparedata = new stdClass();
$preparedata->categoryid = $categoryid;

$types = ['exam', 'resit1', 'resit2'];
foreach ($types as $type) {
    $open = trim($options[$type . 'open']);
    $close = trim($options[$type . 'close']);

    $preparedata->{'update_' . $type} = ($open !== '' && $close !== '') ? 1 : 0;
    $preparedata->{$type . '_idnumber'} = clean_param($options[$type . 'id'], PARAM_ALPHANUMEXT);
    $preparedata->{$type . '_assign_idnumber'} = clean_param(
        $options[$type . 'assignid'],
        PARAM_ALPHANUMEXT
    );

    if ($preparedata->{'update_' . $type}) {
        if (
            $preparedata->{$type . '_idnumber'} === ''
                && $preparedata->{$type . '_assign_idnumber'} === ''
        ) {
            cli_error(get_string('activity_idnumber_required', 'local_examdates') . " ($type)");
        }
        $opents = strtotime($open);
        $closets = strtotime($close);
        if ($opents === false || $closets === false || $closets <= $opents) {
            cli_error(get_string('invalid_dates', 'local_examdates') . " ($type)");
        }
        $preparedata->{$type . 'open'} = $opents;
        $preparedata->{$type . 'close'} = $closets;
    }
}

if (empty($preparedata->update_exam) && empty($preparedata->update_resit1) && empty($preparedata->update_resit2)) {
    cli_error(get_string('select_at_least_one', 'local_examdates'));
}

$manager = new \local_examdates\manager();
$totalcourses = $manager->count_courses_by_category(
    $categoryid,
    (bool)$options['includesub'],
    'local/examdates:manage',
    get_admin()->id
);

if ($totalcourses === 0) {
    cli_writeln(get_string('no_courses_found', 'local_examdates'));
    exit(0);
}

if ($dryrun) {
    cli_writeln(get_string('cli_dryrun', 'local_examdates'));

    $stats = [
        'total_courses' => 0,
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

    for ($offset = 0; $offset < $totalcourses; $offset += \local_examdates\manager::PROCESS_BATCH_SIZE) {
        $courses = $manager->get_courses_by_category(
            $categoryid,
            (bool)$options['includesub'],
            'local/examdates:manage',
            get_admin()->id,
            $offset,
            \local_examdates\manager::PROCESS_BATCH_SIZE
        );

        if (empty($courses)) {
            break;
        }

        $chunkpreview = $manager->get_preview_data($courses, $preparedata);
        foreach ($stats as $key => $unused) {
            $stats[$key] += $chunkpreview['stats'][$key];
        }
    }

    cli_writeln(get_string('preview_stats_message', 'local_examdates', (object)[
        'items' => $stats['total_updates'],
        'quizzes' => $stats['quiz_updates'],
        'assignments' => $stats['assign_updates'],
        'courses' => $stats['courses_with_changes'],
        'errors'  => $stats['total_errors'],
    ]));

    exit(0);
}

$batchid = $manager->create_batch_id();
$updatedcount = 0;
$errorcount = 0;
$skippedcount = 0;
$changedcoursecount = 0;

for ($offset = 0; $offset < $totalcourses; $offset += \local_examdates\manager::PROCESS_BATCH_SIZE) {
    $courses = $manager->get_courses_by_category(
        $categoryid,
        (bool)$options['includesub'],
        'local/examdates:manage',
        get_admin()->id,
        $offset,
        \local_examdates\manager::PROCESS_BATCH_SIZE
    );

    if (empty($courses)) {
        break;
    }

    $result = $manager->apply_updates($courses, $preparedata, get_admin()->id, $batchid, false);
    foreach ($result['errors'] as $error) {
        cli_writeln($error);
    }

    $updatedcount += count($result['updated']);
    $errorcount += count($result['errors']);
    $skippedcount += count($result['skipped']);
    $changedcoursecount += count(array_unique(array_column($result['updated'], 'courseid')));
}

$manager->trigger_batch_event(
    get_admin()->id,
    $batchid,
    $updatedcount,
    $categoryid,
    $changedcoursecount
);

cli_writeln(get_string('cli_success', 'local_examdates', (object)[
    'updated' => $updatedcount,
    'total'   => $updatedcount + $errorcount + $skippedcount,
]));

exit(0);
