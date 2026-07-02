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
 * Privacy provider for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdates\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;
use context;
use context_system;

defined('MOODLE_INTERNAL') || die();

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_examdates_log', [
            'userid'      => 'privacy:metadata:local_examdates_log:userid',
            'courseid'    => 'privacy:metadata:local_examdates_log:courseid',
            'quizid'      => 'privacy:metadata:local_examdates_log:quizid',
            'timecreated' => 'privacy:metadata:local_examdates_log:timecreated',
            'ip_address'  => 'privacy:metadata:local_examdates_log:ip_address',
        ], 'privacy:metadata:local_examdates_log');

        return $collection;
    }

    /**
     * Log entries are a site-wide audit trail, so they live in the system context.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        if ($DB->record_exists('local_examdates_log', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Find users who have data in the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof context_system) {
            return;
        }

        $userlist->add_from_sql('userid', "SELECT userid FROM {local_examdates_log}", []);
    }

    /**
     * Export all log entries authored by the user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_system) {
                continue;
            }

            $records = $DB->get_records('local_examdates_log',
                ['userid' => $contextlist->get_user()->id], 'timecreated ASC');

            if (empty($records)) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object)[
                    'course'        => $record->course_fullname,
                    'quiz'          => $record->quiz_name,
                    'idnumber'      => $record->idnumber,
                    'old_timeopen'  => $record->old_timeopen ? transform::datetime($record->old_timeopen) : '',
                    'old_timeclose' => $record->old_timeclose ? transform::datetime($record->old_timeclose) : '',
                    'new_timeopen'  => transform::datetime($record->new_timeopen),
                    'new_timeclose' => transform::datetime($record->new_timeclose),
                    'ip_address'    => $record->ip_address,
                    'timecreated'   => transform::datetime($record->timecreated),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_examdates')],
                (object)['logs' => $data]
            );
        }
    }

    /**
     * Delete every log entry in the given context.
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context instanceof context_system) {
            $DB->delete_records('local_examdates_log');
        }
    }

    /**
     * Delete log entries for one user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_system) {
                $DB->delete_records('local_examdates_log',
                    ['userid' => $contextlist->get_user()->id]);
            }
        }
    }

    /**
     * Delete log entries for a set of users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_system) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_examdates_log', "userid $insql", $params);
    }
}