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
 * Filter form for the exam dates change history.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdates\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class history_filter_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $cd = $this->_customdata;

        $courses   = isset($cd['courses']) ? $cd['courses'] : [];
        $users     = isset($cd['users']) ? $cd['users'] : [];
        $idnumbers = isset($cd['idnumbers']) ? $cd['idnumbers'] : [];
        $expanded  = !empty($cd['expanded']);

        $mform->addElement('header', 'filterheader', get_string('show_filters', 'local_examdates'));
        $mform->setExpanded('filterheader', $expanded);

        // Course filter (options are limited to courses that appear in the log).
        $courseoptions = [0 => get_string('all')] + $courses;
        $mform->addElement('select', 'filtercourse',
            get_string('filter_course', 'local_examdates'), $courseoptions);
        $mform->setType('filtercourse', PARAM_INT);

        // User filter.
        $useroptions = [0 => get_string('all')] + $users;
        $mform->addElement('select', 'filteruser',
            get_string('filter_user', 'local_examdates'), $useroptions);
        $mform->setType('filteruser', PARAM_INT);

        // Test type (idnumber) filter.
        $idoptions = ['' => get_string('all')] + $idnumbers;
        $mform->addElement('select', 'filteridnumber',
            get_string('filter_idnumber', 'local_examdates'), $idoptions);
        $mform->setType('filteridnumber', PARAM_ALPHANUMEXT);

        // Date range (both optional).
        $mform->addElement('date_selector', 'filterfrom',
            get_string('filter_date_from', 'local_examdates'), ['optional' => true]);
        $mform->addElement('date_selector', 'filterto',
            get_string('filter_date_to', 'local_examdates'), ['optional' => true]);

        $mform->addElement('submit', 'applyfilter', get_string('filter'));
    }
}
