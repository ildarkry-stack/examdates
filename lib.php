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
 * Library callbacks for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a link to the category-level exam dates preview to that category's own
 * admin menu.
 *
 * This is what makes local/examdates:preview actually reachable: index.php
 * lives under Site administration and requires local/examdates:manage
 * site-wide, which most teachers will never have. This link instead appears
 * directly on the category the user is looking at, and is shown to anyone
 * holding either capability in that specific category context.
 *
 * @param navigation_node $navigation
 * @param context_coursecat $context
 */
function local_examdates_extend_navigation_category_settings(navigation_node $navigation, context_coursecat $context) {
    $canaccess = has_capability('local/examdates:manage', $context)
        || has_capability('local/examdates:preview', $context);

    if (!$canaccess) {
        return;
    }

    $url = new moodle_url('/local/examdates/preview.php', ['categoryid' => $context->instanceid]);

    $navigation->add(
        get_string('preview_menu', 'local_examdates'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_examdates_preview'
    );
}
