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
 * Capabilities for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Manage exam dates within a course category.
    'local/examdates:manage' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'editingteacher' => CAP_PREVENT,
            'teacher'        => CAP_PREVENT,
        ],
        'clonepermissionsfrom' => 'moodle/category:manage',
    ],

    // View (preview) exam date changes without applying them.
    'local/examdates:preview' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
        ],
    ],

    // Bulk update via CLI (admins / managers only).
    'local/examdates:bulkupdate' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
];