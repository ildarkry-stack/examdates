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
 * English language strings for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['activity'] = 'Activity';
$string['activity_idnumber_required'] = 'Enter an ID number for at least one activity (Test or Assignment)';
$string['apply'] = 'Apply changes';
$string['apply_complete_subject'] = 'Assessment date update finished';
$string['apply_queued'] = 'The change has been queued and will be applied in the background. You will get a notification when it is done - check the change history for the result.';
$string['arrow'] = '→';
$string['assign_idnumber'] = 'Assignment ID number';
$string['assign_idnumber_help'] = 'Course-module ID number of the Assignment activity to update. Leave blank to update only the Test for this period. The opening date is applied to Allow submissions from and the closing date to Due date. Enabled Cut-off date and Grading due date values that would fall before the new Due date are moved forward to the new Due date.';
$string['assignment'] = 'Assignment';
$string['back'] = 'Back';
$string['cancel'] = 'Cancel';
$string['category'] = 'Course category';
$string['changed_at'] = 'Changed at';
$string['changed_by'] = 'Changed by';
$string['changes_applied_detailed'] = 'Changes successfully applied: {$a->items} activities in {$a->courses} courses';
$string['cli_dryrun'] = 'Preview mode (changes will not be saved)';
$string['cli_error_category'] = 'Category with ID {$a} not found';
$string['cli_success'] = 'Updated {$a->updated} activities out of {$a->total}';
$string['cli_usage'] = 'Usage: php update_exam_dates.php --categoryid=ID --examopen="YYYY-MM-DD HH:MM" --examclose="..." [--examid=exam] [--examassignid=ID] [--resit1open=...] [--resit1assignid=ID] [--resit2open=...] [--resit2assignid=ID] [--dryrun]';
$string['confirm_apply_text'] = '<strong>{$a->items}</strong> activities in <strong>{$a->courses}</strong> courses will be changed. Continue?';
$string['confirm_apply_text_paged'] = 'The preview is paginated for performance. Applying will process all <strong>{$a->total}</strong> courses in the selected scope in bounded background batches. Continue?';
$string['confirm_apply_title'] = 'Confirm applying changes';
$string['course'] = 'Course';
$string['course_deleted'] = 'Course deleted';
$string['dateclose'] = 'Closing / due date and time';
$string['dateopen'] = 'Opening date and time';
$string['default_category'] = 'Default category';
$string['default_category_desc'] = 'Category that will be selected by default when opening the management page';
$string['enable_logging'] = 'Enable logging';
$string['enable_logging_desc'] = 'Record all date changes in the log (recommended). Disabling this also disables history and rollback.';
$string['error_activitydeleted'] = 'Cannot roll back: the activity no longer exists';
$string['error_coursedeleted'] = 'Cannot roll back: the course no longer exists';
$string['error_lognotfound'] = 'Log entry not found';
$string['error_nopermission'] = 'You do not have permission to manage assessment dates in this category';
$string['error_nopermission_preview'] = 'You do not have permission to preview assessment dates';
$string['error_quizdeleted'] = 'Cannot roll back: the activity no longer exists';
$string['errors'] = 'Missing activities / errors';
$string['event_dates_updated'] = 'Assessment activity dates updated';
$string['exam'] = 'Exam';
$string['examdates'] = 'Assessment Dates';
$string['examdates:bulkupdate'] = 'Bulk update assessment dates via CLI';
$string['examdates:manage'] = 'Manage assessment activity dates';
$string['examdates:preview'] = 'Preview assessment date changes';
$string['export_csv'] = 'Export to CSV';
$string['filter_course'] = 'Filter by course';
$string['filter_date_from'] = 'Period from';
$string['filter_date_to'] = 'Period to';
$string['filter_idnumber'] = 'Activity ID number';
$string['filter_user'] = 'Filter by user';
$string['found_assignments'] = 'Assignments found';
$string['found_quizzes'] = 'Tests found';
$string['go_to_manage'] = 'Go to management page';
$string['history_empty'] = 'Change history is empty';
$string['history_title'] = 'Assessment date change history';
$string['idnumber'] = 'ID number';
$string['idnumber_help'] = 'Course-module ID number used to identify an activity inside each course.';
$string['idnumber_required'] = 'ID number is required';
$string['include_subcategories'] = 'Include subcategories';
$string['invalid_dates'] = 'Invalid dates: closing date must be after opening date';
$string['log_retention_days'] = 'Log retention period (days)';
$string['log_retention_days_desc'] = 'After how many days automatically delete log entries (0 - do not delete)';
$string['messageprovider:apply_complete'] = 'Bulk assessment date update completion notification';
$string['missing_activity_idnumber'] = 'Course "{$a->coursename}" does not contain {$a->activity} with idnumber = "{$a->idnumber}"';
$string['missing_idnumber'] = 'Course "{$a->coursename}" is missing an activity with idnumber = "{$a->idnumber}"';
$string['new_dates'] = 'New dates';
$string['no_changes_made'] = 'No changes were applied';
$string['no_courses_found'] = 'No courses found in the selected category';
$string['no_limit'] = 'no limit';
$string['nochanges'] = 'No changes';
$string['not_selected'] = 'not selected';
$string['notfound'] = 'Not found';
$string['old_dates'] = 'Old dates';
$string['pluginname'] = 'Exam and Assignment Dates Management';
$string['preview'] = 'Preview';
$string['preview_activity_summary'] = 'Will change: {$a->quizzes} tests and {$a->assignments} assignments. Missing activities / errors: {$a->errors}.';
$string['preview_expired'] = 'This preview has expired. Submit the form again to create a new preview.';
$string['preview_heading'] = 'Assessment dates preview: {$a}';
$string['preview_menu'] = 'Assessment dates preview';
$string['preview_page_stats_message'] = 'Current page: {$a->items} activities will change ({$a->quizzes} tests, {$a->assignments} assignments) in {$a->courses} courses; missing/errors: {$a->errors}. Showing {$a->shown} of {$a->total} courses.';
$string['preview_readonly_notice'] = 'You can preview assessment date changes for this category, but you do not have permission to apply them. Ask a category manager if changes are needed.';
$string['preview_stats'] = 'Preview statistics';
$string['preview_stats_message'] = 'Will be changed: {$a->items} activities ({$a->quizzes} tests, {$a->assignments} assignments) in {$a->courses} courses. Missing activities / errors: {$a->errors}.';
$string['privacy:metadata:local_examdates_log'] = 'A log of assessment activity date changes performed by users.';
$string['privacy:metadata:local_examdates_log:activity_name'] = 'The name of the activity whose dates were changed.';
$string['privacy:metadata:local_examdates_log:courseid'] = 'The ID of the course whose activity was changed.';
$string['privacy:metadata:local_examdates_log:extra_data'] = 'Module-specific date values stored so a change can be rolled back safely.';
$string['privacy:metadata:local_examdates_log:instanceid'] = 'The module instance ID of the activity whose dates were changed.';
$string['privacy:metadata:local_examdates_log:ip_address'] = 'The IP address from which the change was made.';
$string['privacy:metadata:local_examdates_log:modulename'] = 'The module type of the activity whose dates were changed.';
$string['privacy:metadata:local_examdates_log:quizid'] = 'Legacy field containing the Test instance ID for Test changes.';
$string['privacy:metadata:local_examdates_log:timecreated'] = 'The time the change was made.';
$string['privacy:metadata:local_examdates_log:userid'] = 'The ID of the user who made the change.';
$string['quiz'] = 'Test';
$string['quiz_idnumber'] = 'Test ID number';
$string['quiz_idnumber_help'] = 'Course-module ID number of the Test activity to update. Leave blank if this period should update only an Assignment.';
$string['records_total'] = 'Total records: {$a}';
$string['reset_filters'] = 'Reset filters';
$string['resit1'] = 'Resit 1';
$string['resit2'] = 'Resit 2';
$string['rollback'] = 'Rollback';
$string['rollback_confirm'] = 'Are you sure you want to rollback these changes?';
$string['rollback_error'] = 'Rollback error for {$a->activityname} in course {$a->coursename}';
$string['rollback_notice'] = 'Rollback is only possible for the latest change of each activity';
$string['rollback_success'] = 'Successfully rolled back {$a->activityname} in course {$a->coursename}';
$string['select_at_least_one'] = 'Select at least one assessment period to update';
$string['settings'] = 'Settings';
$string['show_filters'] = 'Show filters';
$string['task_apply_updates'] = 'Apply bulk assessment date changes';
$string['task_clean_logs'] = 'Clean up old assessment date log entries';
$string['update_exam_dates'] = 'Update exam-period dates';
$string['update_resit1_dates'] = 'Update resit 1 dates';
$string['update_resit2_dates'] = 'Update resit 2 dates';
$string['view_history'] = 'View change history';
