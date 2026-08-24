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
 * Scheduled task to purge old exam-date log entries.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdates\task;

/**
 * Scheduled task class that purges old exam-date log entries.
 */
class clean_logs extends \core\task\scheduled_task {
    /**
     * Return the task's localised name (shown on the scheduled tasks admin page).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_clean_logs', 'local_examdates');
    }

    /**
     * Run the cleanup.
     */
    public function execute() {
        $manager = new \local_examdates\manager();
        $manager->clean_old_logs();
    }
}
