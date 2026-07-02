# Changelog

All notable changes to this plugin are documented in this file.

## [1.1.2] - 2026-06-05

### Fixed
- Log table schema now matches the data written by the logger (added
  categoryid, course_fullname, quiz_name, action_type, batch_id, ip_address);
  added an upgrade step for existing installations.
- Quiz calendar events are updated when dates change.
- Server-side re-validation of date ranges on apply.
- Preview no longer emits notices for unchanged quizzes.
- History user lookups batched to avoid N+1 queries.

### Added
- Change history filters (course, user, test type, date range), pagination and
  CSV export.
- Custom event `\local_examdates\event\dates_updated`.
- Privacy (GDPR) provider.
- Settings: enable logging, log retention, default category.
- Daily scheduled task to purge old log entries.
- Clickable course names in preview and result tables.

### Changed
- Capability-aware, hierarchical category picker.
- Dates displayed with the user's timezone and locale.
- `batch_id` generated with `random_string()`.
