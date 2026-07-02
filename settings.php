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
 * Admin settings and links for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // Management page (the actual tool).
    $ADMIN->add('localplugins',
        new admin_externalpage(
            'local_examdates',
            get_string('pluginname', 'local_examdates'),
            new moodle_url('/local/examdates/index.php'),
            'local/examdates:manage'
        )
    );

    // Settings page.
    $settings = new admin_settingpage('local_examdates_settings',
        get_string('settings', 'local_examdates'));
    $ADMIN->add('localplugins', $settings);

    // Enable logging.
    $settings->add(new admin_setting_configcheckbox(
        'local_examdates/enable_logging',
        get_string('enable_logging', 'local_examdates'),
        get_string('enable_logging_desc', 'local_examdates'),
        1
    ));

    // Log retention period in days (0 = keep forever).
    $settings->add(new admin_setting_configtext(
        'local_examdates/log_retention_days',
        get_string('log_retention_days', 'local_examdates'),
        get_string('log_retention_days_desc', 'local_examdates'),
        0,
        PARAM_INT
    ));

    // Default category.
    $categoryoptions = ['' => get_string('not_selected', 'local_examdates')]
        + \core_course_category::make_categories_list('local/examdates:manage');
    $settings->add(new admin_setting_configselect(
        'local_examdates/default_category',
        get_string('default_category', 'local_examdates'),
        get_string('default_category_desc', 'local_examdates'),
        '',
        $categoryoptions
    ));
}