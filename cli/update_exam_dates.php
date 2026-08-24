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
 * CLI tool to bulk-update exam/resit quiz dates for a course category.
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
require_once($CFG->libdir . '/cronlib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help'        => false,
        'categoryid'  => 0,
        'includesub'  => 1,
        'examopen'    => '',
        'examclose'   => '',
        'examid'      => 'exam',
        'resit1open'  => '',
        'resit1close' => '',
        'resit1id'    => 'resit1',
        'resit2open'  => '',
        'resit2close' => '',
        'resit2id'    => 'resit2',
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
cron_setup_user();
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

    if ($preparedata->{'update_' . $type}) {
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
$courses = $manager->get_courses_by_category(
    $categoryid,
    (bool)$options['includesub'],
    'local/examdates:manage',
    get_admin()->id
);

if (empty($courses)) {
    cli_writeln(get_string('no_courses_found', 'local_examdates'));
    exit(0);
}

if ($dryrun) {
    cli_writeln(get_string('cli_dryrun', 'local_examdates'));

    $preview = $manager->get_preview_data($courses, $preparedata);
    $stats = $preview['stats'];

    cli_writeln(get_string('preview_stats_message', 'local_examdates', (object)[
        'tests'   => $stats['total_updates'],
        'courses' => $stats['courses_with_changes'],
        'errors'  => $stats['total_errors'],
    ]));

    exit(0);
}

$result = $manager->apply_updates($courses, $preparedata, get_admin()->id);

foreach ($result['errors'] as $error) {
    cli_writeln($error);
}

cli_writeln(get_string('cli_success', 'local_examdates', (object)[
    'updated' => count($result['updated']),
    'total'   => count($result['updated']) + count($result['errors']) + count($result['skipped']),
]));

exit(0);
