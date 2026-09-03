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

/**
 * Category/activity-type/date selection form, used by both index.php (full
 * category picker) and preview.php (category fixed via customdata).
 */
class examdates_form extends \moodleform {
    /**
     * Build the category picker (or static display, if fixed), the three
     * exam/resit blocks with optional Quiz and Assignment targets and the preview submit button.
     */
    public function definition() {
        $mform = $this->_form;

        $fixedcategoryid = !empty($this->_customdata['fixedcategoryid'])
            ? (int)$this->_customdata['fixedcategoryid'] : 0;

        if ($fixedcategoryid) {
            // Reached from a specific category's admin menu (preview.php): the
            // category is not a free choice, so show it as read-only.
            $categoryname = isset($this->_customdata['fixedcategoryname'])
                ? $this->_customdata['fixedcategoryname'] : '';
            $mform->addElement(
                'static',
                'categoryid_display',
                get_string('category', 'local_examdates'),
                $categoryname
            );
            $mform->addElement('hidden', 'categoryid', $fixedcategoryid);
            $mform->setType('categoryid', PARAM_INT);
        } else {
            // Category selection: capability-aware and hierarchical.
            $categories = \core_course_category::make_categories_list('local/examdates:manage');

            $mform->addElement(
                'select',
                'categoryid',
                get_string('category', 'local_examdates'),
                $categories
            );
            $mform->setType('categoryid', PARAM_INT);
            $mform->addRule('categoryid', null, 'required', null, 'client');

            // A category passed in via the URL (e.g. the "manage" link from the
            // preview page) takes priority over the configured default.
            $preset = !empty($this->_customdata['presetcategoryid'])
                ? (int)$this->_customdata['presetcategoryid'] : 0;
            if ($preset && isset($categories[$preset])) {
                $mform->setDefault('categoryid', $preset);
            } else {
                $default = get_config('local_examdates', 'default_category');
                if ($default && isset($categories[$default])) {
                    $mform->setDefault('categoryid', $default);
                }
            }
        }

        // Include subcategories.
        $mform->addElement(
            'advcheckbox',
            'include_sub',
            get_string('include_subcategories', 'local_examdates')
        );
        $mform->setType('include_sub', PARAM_INT);
        $mform->setDefault('include_sub', 1);

        // Build the three identical blocks.
        $this->add_activity_block($mform, 'exam', 1);
        $this->add_activity_block($mform, 'resit1', 0);
        $this->add_activity_block($mform, 'resit2', 0);

        // Submit button, rendered as a standard always-visible action bar
        // (no separate collapsible "Actions" header, no cancel button here).
        $this->add_action_buttons(false, get_string('preview', 'local_examdates'));
    }

    /**
     * Add a checkbox + Quiz/Assignment idnumbers + open/close block for one assessment type.
     *
     * @param \MoodleQuickForm $mform
     * @param string $type exam|resit1|resit2
     * @param int $defaultchecked
     */
    private function add_activity_block($mform, $type, $defaultchecked) {
        $mform->addElement('header', $type . 'header', get_string($type, 'local_examdates'));

        $mform->addElement(
            'advcheckbox',
            'update_' . $type,
            get_string('update_' . $type . '_dates', 'local_examdates')
        );
        $mform->setType('update_' . $type, PARAM_INT);
        $mform->setDefault('update_' . $type, $defaultchecked);

        $mform->addElement('text', $type . '_idnumber', get_string('quiz_idnumber', 'local_examdates'));
        $mform->setType($type . '_idnumber', PARAM_ALPHANUMEXT);
        $mform->setDefault($type . '_idnumber', $type);
        $mform->addHelpButton($type . '_idnumber', 'quiz_idnumber', 'local_examdates');
        $mform->disabledIf($type . '_idnumber', 'update_' . $type, 'notchecked');

        $mform->addElement(
            'text',
            $type . '_assign_idnumber',
            get_string('assign_idnumber', 'local_examdates')
        );
        $mform->setType($type . '_assign_idnumber', PARAM_ALPHANUMEXT);
        $mform->setDefault($type . '_assign_idnumber', '');
        $mform->addHelpButton($type . '_assign_idnumber', 'assign_idnumber', 'local_examdates');
        $mform->disabledIf($type . '_assign_idnumber', 'update_' . $type, 'notchecked');

        $mform->addElement(
            'date_time_selector',
            $type . 'open',
            get_string('dateopen', 'local_examdates')
        );
        $mform->disabledIf($type . 'open', 'update_' . $type, 'notchecked');
        $this->set_default_time($mform, $type . 'open', 0, 1);

        $mform->addElement(
            'date_time_selector',
            $type . 'close',
            get_string('dateclose', 'local_examdates')
        );
        $mform->disabledIf($type . 'close', 'update_' . $type, 'notchecked');
        $this->set_default_time($mform, $type . 'close', 23, 59);
    }

    /**
     * Set a sensible default timestamp (today at hour:minute, user timezone).
     *
     * @param \MoodleQuickForm $mform The form to set the default on
     * @param string $elementname Name of the date_time_selector element
     * @param int $hour Default hour
     * @param int $minute Default minute
     */
    private function set_default_time($mform, $elementname, $hour, $minute) {
        $defaultdate = usergetdate(time());
        $timestamp = make_timestamp(
            $defaultdate['year'],
            $defaultdate['mon'],
            $defaultdate['mday'],
            $hour,
            $minute
        );
        $mform->setDefault($elementname, $timestamp);
    }

    /**
     * Server-side validation: at least one assessment type must be selected, each
     * selected type needs at least one activity idnumber, and close must be after open.
     *
     * @param array $data Submitted form data
     * @param array $files Submitted files (unused)
     * @return array Field name => error message
     */
    public function validation($data, $files) {
        $errors = [];

        if (empty($data['update_exam']) && empty($data['update_resit1']) && empty($data['update_resit2'])) {
            $errors['categoryid'] = get_string('select_at_least_one', 'local_examdates');
        }

        foreach (['exam', 'resit1', 'resit2'] as $type) {
            if (empty($data['update_' . $type])) {
                continue;
            }

            $quizidnumber = trim($data[$type . '_idnumber'] ?? '');
            $assignidnumber = trim($data[$type . '_assign_idnumber'] ?? '');
            if ($quizidnumber === '' && $assignidnumber === '') {
                $errors[$type . '_idnumber'] = get_string('activity_idnumber_required', 'local_examdates');
            }

            if ($data[$type . 'close'] <= $data[$type . 'open']) {
                $errors[$type . 'close'] = get_string('invalid_dates', 'local_examdates');
            }
        }

        return $errors;
    }
}
