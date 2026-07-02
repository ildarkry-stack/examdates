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

// Main.
$string['pluginname'] = 'Exam Dates Management';
$string['examdates'] = 'Exam Dates';
$string['manageexamdates'] = 'Manage Exam Dates';
$string['examdates:manage'] = 'Manage exam test dates';
$string['examdates:preview'] = 'Preview exam date changes';
$string['examdates:bulkupdate'] = 'Bulk update exam dates via CLI';

// Test types.
$string['exam'] = 'Exam';
$string['resit1'] = 'Resit 1';
$string['resit2'] = 'Resit 2';
$string['quiztype'] = 'Test type';

// Category selection form.
$string['category'] = 'Course category';
$string['category_desc'] = 'Select the category containing courses to update';
$string['include_subcategories'] = 'Include subcategories';
$string['include_subcategories_desc'] = 'Apply changes to all subcategories of the selected category as well';

// Dates and time.
$string['date'] = 'Date';
$string['dateopen'] = 'Opening date and time';
$string['dateclose'] = 'Closing date and time';
$string['update_dates'] = 'Update dates';
$string['select_at_least_one'] = 'Select at least one test type to update';
$string['exam_open'] = 'Exam opening date';
$string['exam_close'] = 'Exam closing date';
$string['resit1_open'] = 'Resit 1 opening date';
$string['resit1_close'] = 'Resit 1 closing date';
$string['resit2_open'] = 'Resit 2 opening date';
$string['resit2_close'] = 'Resit 2 closing date';

// Buttons and actions.
$string['preview'] = 'Preview';
$string['apply'] = 'Apply changes';
$string['apply_confirm'] = 'Are you sure you want to apply these changes?';
$string['cancel'] = 'Cancel';
$string['back'] = 'Back';
$string['continue'] = 'Continue';
$string['rollback'] = 'Rollback';
$string['rollback_confirm'] = 'Are you sure you want to rollback these changes?';
$string['refresh'] = 'Refresh';
$string['update_exam_dates'] = 'Update exam dates';
$string['update_resit1_dates'] = 'Update resit 1 dates';
$string['update_resit2_dates'] = 'Update resit 2 dates';
$string['not_selected'] = 'not selected';

// Results and statuses.
$string['found'] = 'Found';
$string['notfound'] = 'Not found';
$string['found_quizzes'] = 'Tests found';
$string['errors'] = 'Missing tests / errors';
$string['success'] = 'Success';
$string['warning'] = 'Warning';
$string['error'] = 'Error';
$string['skipped'] = 'Skipped';
$string['nochanges'] = 'No changes';
$string['updated'] = 'Updated';
$string['failed'] = 'Failed';
$string['status'] = 'Status';

// Messages.
$string['no_courses_found'] = 'No courses found in the selected category';
$string['no_quizzes_found'] = 'No tests with exam/resit1/resit2 identifiers found in course "{$a}"';
$string['no_changes_made'] = 'No changes were applied';
$string['changes_applied'] = 'Changes successfully applied for {$a} courses';
$string['changes_applied_detailed'] = 'Changes successfully applied: {$a->tests} tests in {$a->courses} courses';
$string['changes_preview'] = 'Preview of changes';
$string['update_success'] = 'Dates for test "{$a->quizname}" in course "{$a->coursename}" successfully updated';
$string['update_error'] = 'Error updating test "{$a->quizname}" in course "{$a->coursename}"';
$string['missing_idnumber'] = 'Course "{$a->coursename}" is missing a test with idnumber = "{$a->idnumber}"';
$string['invalid_dates'] = 'Invalid dates: closing date must be after opening date';
$string['date_must_be_future'] = 'Date must be in the future';
$string['timezone_warning'] = 'Warning: time is displayed in server timezone ({$a})';

// Change history.
$string['history'] = 'History';
$string['history_title'] = 'Exam dates change log';
$string['history_empty'] = 'Change history is empty';
$string['changed_by'] = 'Changed by';
$string['changed_at'] = 'Changed at';
$string['course'] = 'Course';
$string['quiz'] = 'Test';
$string['old_dates'] = 'Old dates';
$string['new_dates'] = 'New dates';
$string['old_date_range'] = 'Old period: {$a->open} — {$a->close}';
$string['new_date_range'] = 'New period: {$a->open} — {$a->close}';
$string['date_format'] = 'd.m.Y H:i';
$string['no_limit'] = 'no limit';
$string['batch_operation'] = 'Batch operation';

// History filters.
$string['filter_course'] = 'Filter by course';
$string['filter_user'] = 'Filter by user';
$string['filter_date_from'] = 'Period from';
$string['filter_date_to'] = 'Period to';
$string['filter_idnumber'] = 'Test type';
$string['show_filters'] = 'Show filters';
$string['reset_filters'] = 'Reset filters';
$string['export_csv'] = 'Export to CSV';
$string['perpage'] = 'Records per page';
$string['records_total'] = 'Total records: {$a}';
 = 'Export to CSV';
 = 'Records per page';
 = 'Total records: {}';

// Rollback.
$string['rollback_success'] = 'Successfully rolled back for {$a->quizname} in course {$a->coursename}';
$string['rollback_error'] = 'Rollback error for {$a->quizname} in course {$a->coursename}';
$string['rollback_notice'] = 'Rollback is only possible for the last change of each test';

// Reports.
$string['report'] = 'Report';
$string['report_summary'] = 'Summary report';
$string['total_courses'] = 'Total courses';
$string['total_updates'] = 'Total updates';
$string['last_update'] = 'Last update';
$string['most_active_user'] = 'Most active user';
$string['most_updated_course'] = 'Course with most changes';

// Plugin settings.
$string['settings'] = 'Settings';
$string['default_category'] = 'Default category';
$string['default_category_desc'] = 'Category that will be selected by default when opening the management page';
$string['enable_logging'] = 'Enable logging';
$string['enable_logging_desc'] = 'Record all date changes in the log (recommended). Disabling this also disables history and rollback.';
$string['log_retention_days'] = 'Log retention period (days)';
$string['log_retention_days_desc'] = 'After how many days automatically delete log entries (0 - do not delete)';

// Permissions.
$string['error_nopermission'] = 'You do not have permission to manage exam dates in this category';
$string['error_nopermission_preview'] = 'You do not have permission to preview exam dates';

// CLI scripts.
$string['cli_usage'] = 'Usage: php update_exam_dates.php --categoryid=ID --examopen="YYYY-MM-DD HH:MM" --examclose="..." [--resit1open=...] [--resit2open=...] [--dryrun]';
$string['cli_dryrun'] = 'Preview mode (changes will not be saved)';
$string['cli_success'] = 'Updated {$a->updated} tests out of {$a->total}';
$string['cli_error_category'] = 'Category with ID {$a} not found';

// Help tooltips.
$string['helpexam'] = 'Exam test (used in formula as [[exam]])';
$string['helpresit1'] = 'First resit (used in formula as [[resit1]])';
$string['helpresit2'] = 'Second resit (used in formula as [[resit2]])';
$string['helpidnumber'] = 'Tests must have idnumber: exam, resit1, resit2';
$string['helpcategory'] = 'All courses in the selected category will be processed';

// Preview and confirmation.
$string['preview_stats'] = 'Preview statistics';
$string['confirm_apply_title'] = 'Confirm applying changes';
$string['confirm_apply_text'] = '<strong>{$a->tests}</strong> tests in <strong>{$a->courses}</strong> courses will be changed. Continue?';
$string['confirm_apply_button'] = 'Yes, apply changes';
$string['view_history'] = 'View change history';

// ID numbers.
$string['idnumber'] = 'ID number';
$string['idnumber_required'] = 'ID number is required';
$string['idnumber_desc'] = 'ID number of the test in module settings (e.g.: exam, final_test, exam_2024)';
$string['idnumber_help'] = 'The ID number of the test that you set in the "Test" module settings. Usually "exam", "resit1", "resit2", but you can specify any unique value.';

// Other.
$string['arrow'] = '→';
$string['preview_stats_message'] = 'Will be changed: {$a->tests} tests in {$a->courses} courses. Skipped (tests not found): {$a->errors}.';

// Actions (form header).
$string['actions'] = 'Actions';

// Events.
$string['event_dates_updated'] = 'Exam dates updated';

// Scheduled tasks.
$string['task_clean_logs'] = 'Clean up old exam date log entries';

// Privacy (GDPR).
$string['privacy:metadata:local_examdates_log'] = 'A log of exam date changes performed by users.';
$string['privacy:metadata:local_examdates_log:userid'] = 'The ID of the user who made the change.';
$string['privacy:metadata:local_examdates_log:courseid'] = 'The ID of the course whose test was changed.';
$string['privacy:metadata:local_examdates_log:quizid'] = 'The ID of the test whose dates were changed.';
$string['privacy:metadata:local_examdates_log:timecreated'] = 'The time the change was made.';
$string['privacy:metadata:local_examdates_log:ip_address'] = 'The IP address from which the change was made.';