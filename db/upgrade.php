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
 * Upgrade steps for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_examdates plugin database schema.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_local_examdates_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Brings the log table in line with what manager::log_change() actually writes.
    if ($oldversion < 2026060503) {

        $table = new xmldb_table('local_examdates_log');

        // Define and add each previously-missing field (idempotent).
        $fields = [
            new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated'),
            new xmldb_field('course_fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'courseid'),
            new xmldb_field('quiz_name', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'quizid'),
            new xmldb_field('action_type', XMLDB_TYPE_CHAR, '20', null, null, null, 'bulk', 'new_timeclose'),
            new xmldb_field('batch_id', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'action_type'),
            new xmldb_field('ip_address', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'batch_id'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Widen idnumber to match PARAM_ALPHANUMEXT inputs.
        $idnumber = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'quiz_name');
        if ($dbman->field_exists($table, $idnumber)) {
            $dbman->change_field_precision($table, $idnumber);
        }

        // Add helpful indexes.
        $indexes = [
            new xmldb_index('categoryid_idx', XMLDB_INDEX_NOTUNIQUE, ['categoryid']),
            new xmldb_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']),
            new xmldb_index('batchid_idx', XMLDB_INDEX_NOTUNIQUE, ['batch_id']),
        ];
        foreach ($indexes as $index) {
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        upgrade_plugin_savepoint(true, 2026060503, 'local', 'examdates');
    }

    return true;
}