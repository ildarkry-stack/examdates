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
$string['apply'] = 'Apply changes';
$string['apply_complete_subject'] = 'Exam dates update finished';
$string['apply_queued'] = 'The change has been queued and will be applied in the background. You will get a notification when it is done - check the change history for the result.';
$string['arrow'] = '→';
$string['back'] = 'Back';
$string['cancel'] = 'Cancel';
$string['category'] = 'Course category';
$string['changed_at'] = 'Changed at';
$string['changed_by'] = 'Changed by';
$string['changes_applied_detailed'] = 'Changes successfully applied: {$a->tests} tests in {$a->courses} courses';
$string['cli_dryrun'] = 'Preview mode (changes will not be saved)';
$string['cli_error_category'] = 'Category with ID {$a} not found';
$string['cli_success'] = 'Updated {$a->updated} tests out of {$a->total}';
$string['cli_usage'] = 'Usage: php update_exam_dates.php --categoryid=ID --examopen="YYYY-MM-DD HH:MM" --examclose="..." [--resit1open=...] [--resit2open=...] [--dryrun]';
$string['confirm_apply_text'] = '<strong>{$a->tests}</strong> tests in <strong>{$a->courses}</strong> courses will be changed. Continue?';
$string['confirm_apply_text_paged'] = 'The preview is paginated for performance. Applying will process all <strong>{$a->total}</strong> courses in the selected scope in bounded background batches. Continue?';
$string['confirm_apply_title'] = 'Confirm applying changes';
$string['course'] = 'Course';
$string['course_deleted'] = 'Course deleted';
$string['dateclose'] = 'Closing date and time';
$string['dateopen'] = 'Opening date and time';
$string['default_category'] = 'Default category';
$string['default_category_desc'] = 'Category that will be selected by default when opening the management page';
$string['enable_logging'] = 'Enable logging';
$string['enable_logging_desc'] = 'Record all date changes in the log (recommended). Disabling this also disables history and rollback.';
$string['error_coursedeleted'] = 'Cannot roll back: the course no longer exists';
$string['error_lognotfound'] = 'Log entry not found';
$string['error_nopermission'] = 'You do not have permission to manage exam dates in this category';
$string['error_nopermission_preview'] = 'You do not have permission to preview exam dates';
$string['error_quizdeleted'] = 'Cannot roll back: the quiz no longer exists';
$string['errors'] = 'Missing tests / errors';
$string['event_dates_updated'] = 'Exam dates updated';
$string['exam'] = 'Exam';
$string['examdates'] = 'Exam Dates';
$string['examdates:bulkupdate'] = 'Bulk update exam dates via CLI';
$string['examdates:manage'] = 'Manage exam test dates';
$string['examdates:preview'] = 'Preview exam date changes';
$string['export_csv'] = 'Export to CSV';
$string['filter_course'] = 'Filter by course';
$string['filter_date_from'] = 'Period from';
$string['filter_date_to'] = 'Period to';
$string['filter_idnumber'] = 'Test type';
$string['filter_user'] = 'Filter by user';
$string['found_quizzes'] = 'Tests found';
$string['go_to_manage'] = 'Go to management page';
$string['history_empty'] = 'Change history is empty';
$string['history_title'] = 'Exam dates change log';
$string['idnumber'] = 'ID number';
$string['idnumber_help'] = 'The ID number of the test that you set in the "Test" module settings. Usually "exam", "resit1", "resit2", but you can specify any unique value.';
$string['idnumber_required'] = 'ID number is required';
$string['include_subcategories'] = 'Include subcategories';
$string['invalid_dates'] = 'Invalid dates: closing date must be after opening date';
$string['log_retention_days'] = 'Log retention period (days)';
$string['log_retention_days_desc'] = 'After how many days automatically delete log entries (0 - do not delete)';
$string['messageprovider:apply_complete'] = 'Bulk exam date update completion notification';
$string['missing_idnumber'] = 'Course "{$a->coursename}" is missing a test with idnumber = "{$a->idnumber}"';
$string['new_dates'] = 'New dates';
$string['no_changes_made'] = 'No changes were applied';
$string['no_courses_found'] = 'No courses found in the selected category';
$string['no_limit'] = 'no limit';
$string['nochanges'] = 'No changes';
$string['not_selected'] = 'not selected';
$string['notfound'] = 'Not found';
$string['old_dates'] = 'Old dates';
$string['pluginname'] = 'Exam Dates Management';
$string['preview'] = 'Preview';
$string['preview_expired'] = 'This preview has expired. Submit the form again to create a new preview.';
$string['preview_heading'] = 'Exam dates preview: {$a}';
$string['preview_menu'] = 'Exam dates preview';
$string['preview_readonly_notice'] = 'You can preview exam date changes for this category, but you do not have permission to apply them. Ask a category manager if changes are needed.';
$string['preview_stats'] = 'Preview statistics';
$string['preview_stats_message'] = 'Will be changed: {$a->tests} tests in {$a->courses} courses. Skipped (tests not found): {$a->errors}.';
$string['preview_page_stats_message'] = 'Current page: {$a->tests} tests will change in {$a->courses} courses; {$a->errors} tests are missing. Showing {$a->shown} of {$a->total} courses.';
$string['privacy:metadata:local_examdates_log'] = 'A log of exam date changes performed by users.';
$string['privacy:metadata:local_examdates_log:courseid'] = 'The ID of the course whose test was changed.';
$string['privacy:metadata:local_examdates_log:ip_address'] = 'The IP address from which the change was made.';
$string['privacy:metadata:local_examdates_log:quizid'] = 'The ID of the test whose dates were changed.';
$string['privacy:metadata:local_examdates_log:timecreated'] = 'The time the change was made.';
$string['privacy:metadata:local_examdates_log:userid'] = 'The ID of the user who made the change.';
$string['quiz'] = 'Test';
$string['records_total'] = 'Total records: {$a}';
$string['reset_filters'] = 'Reset filters';
$string['resit1'] = 'Resit 1';
$string['resit2'] = 'Resit 2';
$string['rollback'] = 'Rollback';
$string['rollback_confirm'] = 'Are you sure you want to rollback these changes?';
$string['rollback_error'] = 'Rollback error for {$a->quizname} in course {$a->coursename}';
$string['rollback_notice'] = 'Rollback is only possible for the last change of each test';
$string['rollback_success'] = 'Successfully rolled back for {$a->quizname} in course {$a->coursename}';
$string['select_at_least_one'] = 'Select at least one test type to update';
$string['settings'] = 'Settings';
$string['show_filters'] = 'Show filters';
$string['task_apply_updates'] = 'Apply bulk exam date changes';
$string['task_clean_logs'] = 'Clean up old exam date log entries';
$string['update_exam_dates'] = 'Update exam dates';
$string['update_resit1_dates'] = 'Update resit 1 dates';
$string['update_resit2_dates'] = 'Update resit 2 dates';
$string['view_history'] = 'View change history';
