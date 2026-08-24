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
 * Confirmation form that re-posts prepared data to the apply action.
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
 * Hidden-field confirmation form that re-posts the prepared bulk-update data
 * back to index.php's apply action after the person confirms the preview.
 */
class confirm_form extends \moodleform {
    /**
     * Re-post the already-prepared, already-previewed data as hidden fields.
     */
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $data = (array)$customdata['data'];

        // Re-post each prepared value as a hidden field. Values are re-validated
        // and re-typed in index.php when the apply action runs.
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $fieldname = $key . '[' . $k . ']';
                    $mform->addElement('hidden', $fieldname, $v);
                    $mform->setType($fieldname, PARAM_RAW);
                }
            } else {
                $mform->addElement('hidden', $key, $value);
                $mform->setType($key, PARAM_RAW);
            }
        }

        $mform->addElement('hidden', 'action', 'apply');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);

        $buttonarray = [];
        $buttonarray[] = &$mform->createElement(
            'submit',
            'submitbutton',
            get_string('apply', 'local_examdates')
        );
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
