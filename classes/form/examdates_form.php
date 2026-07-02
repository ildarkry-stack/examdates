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
 * Main category/date selection form.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdates\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class examdates_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        // Category selection: capability-aware and hierarchical.
        $categories = \core_course_category::make_categories_list('local/examdates:manage');

        $mform->addElement('select', 'categoryid',
            get_string('category', 'local_examdates'), $categories);
        $mform->setType('categoryid', PARAM_INT);
        $mform->addRule('categoryid', null, 'required', null, 'client');

        // Pre-select the configured default category if present.
        $default = get_config('local_examdates', 'default_category');
        if ($default && isset($categories[$default])) {
            $mform->setDefault('categoryid', $default);
        }

        // Include subcategories.
        $mform->addElement('advcheckbox', 'include_sub',
            get_string('include_subcategories', 'local_examdates'));
        $mform->setType('include_sub', PARAM_INT);
        $mform->setDefault('include_sub', 1);

        // Build the three identical blocks.
        $this->add_quiz_block($mform, 'exam', 1);
        $this->add_quiz_block($mform, 'resit1', 0);
        $this->add_quiz_block($mform, 'resit2', 0);

        // Submit button, rendered as a standard always-visible action bar
        // (no separate collapsible "Actions" header, no cancel button here).
        $this->add_action_buttons(false, get_string('preview', 'local_examdates'));
    }

    /**
     * Add a checkbox + idnumber + open/close block for one quiz type.
     *
     * @param \MoodleQuickForm $mform
     * @param string $type exam|resit1|resit2
     * @param int $defaultchecked
     */
    private function add_quiz_block($mform, $type, $defaultchecked) {
        $mform->addElement('header', $type . 'header', get_string($type, 'local_examdates'));

        $mform->addElement('advcheckbox', 'update_' . $type,
            get_string('update_' . $type . '_dates', 'local_examdates'));
        $mform->setType('update_' . $type, PARAM_INT);
        $mform->setDefault('update_' . $type, $defaultchecked);

        $mform->addElement('text', $type . '_idnumber', get_string('idnumber', 'local_examdates'));
        $mform->setType($type . '_idnumber', PARAM_ALPHANUMEXT);
        $mform->setDefault($type . '_idnumber', $type);
        $mform->addHelpButton($type . '_idnumber', 'idnumber', 'local_examdates');
        $mform->disabledIf($type . '_idnumber', 'update_' . $type, 'notchecked');

        $mform->addElement('date_time_selector', $type . 'open',
            get_string('dateopen', 'local_examdates'));
        $mform->disabledIf($type . 'open', 'update_' . $type, 'notchecked');
        $this->set_default_time($mform, $type . 'open', 0, 1);

        $mform->addElement('date_time_selector', $type . 'close',
            get_string('dateclose', 'local_examdates'));
        $mform->disabledIf($type . 'close', 'update_' . $type, 'notchecked');
        $this->set_default_time($mform, $type . 'close', 23, 59);
    }

    /**
     * Set a sensible default timestamp (today at hour:minute, user timezone).
     */
    private function set_default_time($mform, $elementname, $hour, $minute) {
        $defaultdate = usergetdate(time());
        $timestamp = make_timestamp(
            $defaultdate['year'], $defaultdate['mon'], $defaultdate['mday'], $hour, $minute
        );
        $mform->setDefault($elementname, $timestamp);
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data['update_exam']) && empty($data['update_resit1']) && empty($data['update_resit2'])) {
            $errors['categoryid'] = get_string('select_at_least_one', 'local_examdates');
        }

        foreach (['exam', 'resit1', 'resit2'] as $type) {
            if (empty($data['update_' . $type])) {
                continue;
            }

            if (empty(trim($data[$type . '_idnumber']))) {
                $errors[$type . '_idnumber'] = get_string('idnumber_required', 'local_examdates');
            }

            if ($data[$type . 'close'] <= $data[$type . 'open']) {
                $errors[$type . 'close'] = get_string('invalid_dates', 'local_examdates');
            }
        }

        return $errors;
    }
}