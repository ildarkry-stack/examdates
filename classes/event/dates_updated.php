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
 * Event fired after a batch of exam dates has been updated.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdates\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The dates_updated event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string batch_id: Unique identifier of the bulk operation.
 *      - int updates_count: Number of quizzes updated in this batch.
 *      - int categoryid: Category the operation was launched on (0 if unknown).
 *      - int coursecount: Number of distinct courses affected.
 * }
 */
class dates_updated extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        // No objecttable: the event represents a batch spanning many quizzes.
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_dates_updated', 'local_examdates');
    }

    /**
     * Returns non-localised description of the event for logs.
     *
     * @return string
     */
    public function get_description() {
        $count = isset($this->other['updates_count']) ? (int)$this->other['updates_count'] : 0;
        $courses = isset($this->other['coursecount']) ? (int)$this->other['coursecount'] : 0;
        $batch = isset($this->other['batch_id']) ? $this->other['batch_id'] : '';
        return "The user with id '{$this->userid}' bulk-updated exam dates for {$count} quizzes " .
            "across {$courses} courses (batch '{$batch}').";
    }

    /**
     * Validate the custom data.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['batch_id'])) {
            throw new \coding_exception('The \'batch_id\' value must be set in other.');
        }
        if (!isset($this->other['updates_count'])) {
            throw new \coding_exception('The \'updates_count\' value must be set in other.');
        }
    }
}