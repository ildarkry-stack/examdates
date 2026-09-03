# Exam and Assignment Dates Management (local_examdates)

A Moodle local plugin for bulk-updating assessment dates across many courses in
a category. It supports both **Quiz (`mod_quiz`)** and **Assignment
(`mod_assign`)** activities, with paginated preview, background processing,
change history, CSV export and rollback.

## Description

For each assessment period (exam, resit 1 and resit 2), administrators can
configure an optional Quiz ID number, an optional Assignment ID number, or
both. The plugin finds matching course modules by their **Common module
settings → ID number** and applies the selected dates to every permitted course
in the selected category scope.

Date mapping is:

- Quiz: opening date → `timeopen`; closing date → `timeclose`.
- Assignment: opening date → **Allow submissions from**; closing date →
  **Due date**.
- If an enabled Assignment **Cut-off date** or **Grading due date** would fall
  before the new Due date, it is moved forward to the new Due date so the
  assignment keeps a valid date sequence. The original values are stored in
  the audit log and restored by rollback.

Existing installations remain compatible: the Quiz ID numbers `exam`,
`resit1`, and `resit2` remain the defaults, while Assignment ID number fields
are empty by default. Leaving an Assignment field empty therefore preserves
the plugin's previous Quiz-only behaviour.

## Features

- Bulk update Quiz and Assignment dates for a whole category and optionally
  its subcategories.
- Independent Quiz and Assignment ID numbers for exam, resit 1 and resit 2.
- Quiz-only, Assignment-only, or combined operation for each assessment period.
- Paginated preview (50 courses per page) to avoid materialising large course
  sets in memory.
- Background apply processing in batches of 500 courses.
- N+1-safe activity preloading for both supported module types.
- Change history with filters, pagination and CSV export.
- Per-activity rollback. Assignment-specific secondary dates changed for
  consistency are also restored.
- Moodle calendar event synchronisation after Quiz and Assignment updates and
  rollbacks.
- Category-level read-only preview for `local/examdates:preview` holders.
- Configurable logging and automatic log retention.
- Privacy (GDPR) provider.

## Requirements

- Moodle 4.5, 5.0, or 5.1 (CI matrix in `.github/workflows/moodle-ci.yml`).
- PHP 8.2 or later for the supported deployment matrix.

## Installation / upgrade

1. Copy the plugin folder to `local/examdates` (the folder must be named
   `examdates`).
2. Log in as an administrator and follow the Moodle upgrade prompt, or run
   `php admin/cli/upgrade.php`.
3. Purge caches if required by your deployment process.

Upgrading from 1.4.x adds generic activity fields to the existing audit log.
Existing Quiz history is migrated automatically and remains available for
rollback.

## Usage

1. Open Site administration → Plugins → Exam and Assignment Dates Management.
2. Choose a course category and whether to include subcategories.
3. Enable one or more periods: exam, resit 1, resit 2.
4. For each enabled period, enter at least one target ID number:
   - **Test ID number** for a Quiz;
   - **Assignment ID number** for an Assignment.
5. Select the opening and closing/due dates.
6. Click **Preview**. Each period shows Quiz and Assignment rows separately,
   including missing targets and before/after dates.
7. Click **Apply changes** to queue the background update.
8. Use **View change history** to review, export or roll back changes.

The ID number is the course-module ID number from the activity's Common module
settings. It is not the database instance ID and does not have to match the
activity name.

## CLI

For scripted updates use `cli/update_exam_dates.php`. Example updating both an
exam Quiz and an exam Assignment:

```bash
php local/examdates/cli/update_exam_dates.php \
    --categoryid=5 \
    --examid=exam \
    --examassignid=exam_upload \
    --examopen="2026-06-01 09:00" \
    --examclose="2026-06-01 11:00" \
    --dryrun
```

Drop `--dryrun` to apply the changes. Assignment options are
`--examassignid`, `--resit1assignid`, and `--resit2assignid`. Quiz options
remain `--examid`, `--resit1id`, and `--resit2id`. `--includesub=0` restricts
the operation to the selected category itself.

## Settings

- **Enable logging** — record changes for history and rollback.
- **Log retention period (days)** — automatically delete older log entries
  (`0` keeps them indefinitely).
- **Default category** — category preselected on the management page.

## Capabilities

- `local/examdates:manage` — manage assessment dates.
- `local/examdates:preview` — preview changes.
- `local/examdates:bulkupdate` — bulk update via CLI.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
