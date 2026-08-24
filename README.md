# Exam Dates Management (local_examdates)

A Moodle local plugin for bulk-updating quiz open/close dates (exam, resit1,
resit2) across all courses in a category, with preview, change history and
rollback.

## Description

**Exam Dates Management** lets administrators set exam and resit dates for many
courses at once. You pick a course category (optionally including
subcategories), and the plugin finds quizzes by their ID number (`exam`,
`resit1`, `resit2` by default, but any value can be used) and updates their
open/close dates in bulk. Every run shows a before/after preview, and all
changes are logged so they can be reviewed, exported and rolled back.

## Features

- Bulk update of quiz open/close dates for a whole category (and its
  subcategories, if selected).
- Quizzes are matched by course-module ID number, so any naming scheme works.
- Before/after preview: see exactly what will change before applying anything.
- Change history with filters (course, user, test type, date range),
  pagination and CSV export.
- Per-change rollback to the previous dates.
- Category-level read-only preview for `local/examdates:preview` holders
  (e.g. teachers), reachable from that category's own admin menu - no
  Site administration access required.
- Calendar events are kept in sync when dates change.
- Configurable logging and automatic log retention (scheduled task).
- Privacy (GDPR) provider implemented.

## Requirements

- Moodle 4.5 or later (tested on Moodle 5.1).
- PHP 8.2 or later.

## Installation

1. Copy the plugin folder to `local/examdates` in your Moodle installation
   (the folder must be named `examdates`).
2. Log in as an administrator and follow the upgrade prompt, or run
   `php admin/cli/upgrade.php`.
3. Purge caches (Site administration → Development → Purge caches).

## Usage

1. Go to Site administration → Plugins → Exam Dates Management.
2. Choose a course category (optionally include subcategories).
3. Enable the test types you want to update (exam / resit 1 / resit 2), set the
   ID number the quizzes use, and pick the new open/close dates.
4. Click **Preview** to review the before/after table.
5. Click **Apply changes** to commit, or **Cancel** to go back.
6. Use **View change history** to filter, export (CSV) or roll back changes.

Users who only hold `local/examdates:preview` (not `manage`) - typically
teachers or editing teachers - don't see the Site administration page above
at all. Instead, from **Course category management**, open the category in
question and look for **Exam dates preview** in its admin menu. That opens a
read-only version of the same before/after preview, scoped to that category,
with no way to apply changes.

Quizzes must have an ID number set in their module settings (Quiz settings →
Common module settings → ID number) matching the value entered in the form.

## CLI

For scripted or scheduled bulk updates, use `cli/update_exam_dates.php`:

```
php local/examdates/cli/update_exam_dates.php \
    --categoryid=5 \
    --examopen="2026-06-01 09:00" --examclose="2026-06-01 11:00" \
    --resit1open="2026-07-01 09:00" --resit1close="2026-07-01 11:00" \
    --dryrun
```

Drop `--dryrun` to actually apply the changes. `--includesub=0` restricts the
update to the category itself (subcategories are included by default).
`--examid`, `--resit1id`, `--resit2id` override the default idnumbers
(`exam`, `resit1`, `resit2`).

## Settings

- **Enable logging** — record all changes (required for history and rollback).
- **Log retention (days)** — automatically purge old log entries (0 = keep
  forever); handled by a daily scheduled task.
- **Default category** — pre-selected category on the management page.

## Capabilities

- `local/examdates:manage` — manage exam dates.
- `local/examdates:preview` — preview changes.
- `local/examdates:bulkupdate` — bulk update via CLI.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
