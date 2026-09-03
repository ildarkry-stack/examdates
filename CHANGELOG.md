# Changelog

All notable changes to this plugin are documented in this file.

## [1.4.4] - 2026-09-03

### Fixed
- Fixed Moodle CodeSniffer findings introduced by the pagination changes: added
  the required `@var` tag for the category ID cache property, capitalised the
  affected inline comment, replaced `elseif` with Moodle-style `else if`, and
  restored alphabetical ordering of preview language string keys.

## [1.4.3] - 2026-09-03

### Fixed
- Added bounded course retrieval to `manager::get_courses_by_category()` using
  Moodle DML `limitfrom`/`limitnum` parameters and a matching
  `count_courses_by_category()` query. Web previews no longer materialise an
  entire large category in memory.
- Added standard Moodle pagination to both management and read-only preview
  pages (50 courses per page). Preview form state is kept in the current
  user's session behind a short-lived random token so paging URLs stay small.
- Background apply tasks and the CLI now iterate over the complete selected
  course scope in batches of 500 instead of loading all courses at once. One
  logical bulk operation keeps a shared batch id and emits one aggregate event.
- Descendant category discovery now reads only the selected category branch via
  a recordset, instead of loading every course category on the site.

## [1.4.2] - 2026-09-03

### Fixed
- Added the required `messageprovider:apply_complete` language string for the
  `apply_complete` message provider declared in `db/messages.php`. This fixes
  Moodle Plugin Checker validation and gives the provider a readable name in
  notification preferences.
- Added the equivalent Russian translation for the message provider name.

## [1.4.1] - 2026-09-03

### Fixed
- Removed the N+1 database-read pattern from `manager::get_current_dates()`.
  Matching quiz activities are now preloaded with `get_records_sql()` and
  `get_in_or_equal()` in bounded batches, then the preview loops only read
  from the in-memory result map.
- Reused the same preloaded quiz map in `manager::apply_updates()`, removing
  the repeated course-module and quiz SELECTs from its nested course/type
  loops as well. Database writes remain per changed quiz because each quiz
  must still be updated and have its calendar events synchronised.
- When `apply_updates()` is called with reduced course records that omit the
  category field, missing course categories are now loaded in one batch
  instead of falling back to `get_course()` inside the course loop.

## [1.4.0] - 2026-08-27

### Fixed
- **Critical, install-blocking bug on Moodle 5.0/5.1:** `db/messages.php`
  used `MESSAGE_DEFAULT_LOGGEDIN`/`MESSAGE_DEFAULT_LOGGEDOFF`, which don't
  exist as of at least Moodle 4.5 (our own stated minimum) - they were
  replaced by a single `MESSAGE_DEFAULT_ENABLED` constant. Referencing an
  undefined constant is a fatal `Error` on PHP 8+, which crashed plugin
  installation entirely (`upgrade_noncore()` → `message_update_providers()`
  → require of `db/messages.php`). Confirmed via a real install against a
  live Moodle 5.1 + MariaDB instance: install failed before the fix,
  completed successfully after it.
- **Critical bug on Moodle 5.0/5.1:** `cli/update_exam_dates.php` called
  `cron_setup_user()` to run as the site admin. On Moodle 4.5 this
  (deprecated) function still worked - it internally called
  `\core\cron::setup_user()`. On Moodle 5.0+ its body was reduced to just a
  deprecation notice, silently leaving `$USER` unset and every downstream
  capability check failing. Switched to calling `\core\cron::setup_user()`
  directly. Confirmed on a live Moodle 5.1 instance: `--dryrun` and a real
  apply both now succeed, with the quiz row, calendar events, and audit
  log all updated correctly.
- Also verified end-to-end on the same live 5.1 instance: `rollback_change()`
  (dates correctly reverted, `action_type` correctly recorded as
  `rollback`) and `apply_updates_task` (the background task runs, applies
  dates, and - now that the messages.php fix is in - actually delivers the
  completion notification, which was silently impossible before).

### Changed
- GitHub Actions CI matrix now also tests against `MOODLE_500_STABLE` and
  `MOODLE_501_STABLE` (previously only `MOODLE_405_STABLE`), with PHP 8.1
  excluded for those two branches since both require PHP 8.2 as a minimum
  (confirmed against `admin/environment.xml` in each branch's own source -
  4.5 requires 8.1, 5.0 and 5.1 require 8.2).
- README now states Moodle 4.5/5.0/5.1 support, matching what CI verifies.

## [1.3.5] - 2026-08-26

### Fixed
- **Real bug:** `rollback_success`/`rollback_error` notifications required
  `{$a->quizname}`/`{$a->coursename}` placeholders that `history.php` never
  supplied - users saw the literal, unparsed placeholder text instead of a
  message. Now fetches the log row's denormalised quiz/course names up
  front (works even if rollback then fails because the quiz/course has
  since been deleted) and passes them through.
- `manager::get_history()` sorted only by `timecreated DESC` with no
  secondary key. A single bulk apply stamps every row in the batch with
  the same `time()`, so ties had no stable order between page loads -
  rows could shuffle across pagination boundaries. Added `, id DESC`.
- CSV export in `history.php` output raw `course_fullname`/`quiz_name`
  without `format_string()`, unlike the HTML table which correctly
  filters both - multilang tags etc. would leak into the CSV untouched.
- Removed the unsubstantiated "tested on Moodle 5.1" claim from the
  README - CI only tests `MOODLE_405_STABLE`, so this was never actually
  verified.
- Tidied the Russian `idnumber` string, which redundantly said the same
  thing in Russian and English side by side.
- Documented a rollback edge case in the README: if a quiz is deleted and
  recreated with the same ID number, the new quiz gets a fresh history -
  the old quiz's log entries can no longer be rolled back (expected, not
  a bug, but worth stating rather than leaving it to a support question).

## [1.3.4] - 2026-08-25

### Fixed
- Real CI failures found once GitHub Actions actually ran (`moodle-plugin-ci
  phpcs`/`phpdoc`), not visible in earlier local checks:
  - Both lang files were organised into commented sections
    (`// Main.`, `// Quiz types.`, etc.) rather than strict alphabetical
    key order, which `moodle.Files.LangFilesOrdering` requires with
    `--max-warnings 0`. Reordered every string key alphabetically and
    removed the section comments (68 warnings/file).
  - Three methods had incomplete `@param` docblocks relative to their
    actual signatures: `examdates_form::set_default_time()`,
    `manager::log_change()`, `manager::trigger_event()`.

## [1.3.3] - 2026-08-25

### Changed
- Version bump only, no functional changes - required to resubmit to the
  Moodle plugins directory with a version number higher than the previous
  (failed) submission attempt.

## [1.3.2] - 2026-08-25

### Added
- `.github/workflows/moodle-ci.yml` - GitHub Actions CI, adapted from the
  official `moodlehq/moodle-plugin-ci` `gha.dist.yml` template (PHP
  8.1/8.2/8.3, `MOODLE_405_STABLE`, pgsql + mariadb). The Moodle plugins
  directory's automated checker reads its "test results" from this
  workflow's runs via the GitHub API rather than linting the uploaded ZIP
  itself - without it (or with a private repo), it reports "Log file not
  found" for every step regardless of code quality.

## [1.3.1] - 2026-08-24

### Removed
- `manager::render_summary_table()` - dead code since 1.3.0: it was only
  ever called from index.php's old synchronous apply flow, which no longer
  exists. The `status` and `updated` lang strings, only used inside it,
  went with it.
- The `error` lang string - never actually used via `get_string()`; the
  one place it looked used was `$OUTPUT->notification($error, 'error')`,
  where `'error'` is the CSS/severity class, not a string key.

### Fixed
- `cli/update_exam_dates.php` now passes an explicit `$userid` to
  `get_courses_by_category()`, matching the explicit `$userid` it already
  passed to `apply_updates()` a few lines below, instead of relying on the
  ambient `$USER` `cron_setup_user()` happens to set up.
- `db/uninstall.php` now purges any pending `apply_updates_task` from
  `{task_adhoc}`. Scheduled tasks and capabilities are cleaned up
  automatically by core from `db/tasks.php`/`db/access.php`, but a queued
  *adhoc* task isn't - left pending across an uninstall, it would
  reference a class that no longer exists and fail the next cron run.
- `local_examdates_log.action_type` was hardcoded to `'bulk'` for every
  row, including rollbacks (which were only distinguishable via the
  `rollback_` prefix on `batch_id`) - a column that always holds the same
  value carries no information. `log_change()` now derives it from that
  same `batch_id` prefix it already checks elsewhere, so the column
  actually means something, with no schema change needed.
- Renamed the `apply_queued_subject` lang string to `apply_complete_subject`
  - it's the subject of the background task's *completion* message, not
  of the immediate "queued" notice (which is a separate string,
  `apply_queued`, shown inline on index.php).

## [1.3.0] - 2026-08-24

### Changed
- **Applying a change no longer runs inline in the web request.** Clicking
  "Apply" on index.php now queues an adhoc task
  (`apply_updates_task`) and returns immediately; the actual
  `manager::apply_updates()` call happens in the background via Moodle's
  cron. This removes the previous hard ceiling on how large a category
  could be updated in one go (a synchronous request risked exceeding
  `max_execution_time` well within realistic category sizes - see the
  adversarial review). The requesting user gets a Moodle message
  (new `apply_complete` message provider, `db/messages.php`) when it's
  done, and can also check "View change history" at any time.
- `manager::get_courses_by_category()` and the private
  `require_category_capability()` helper now accept an explicit
  `$userid` parameter (defaulting to the current `$USER`, so every
  existing synchronous caller is unaffected). The background task passes
  the *requesting* user's id explicitly, since there is no "current" web
  user during a cron run - relying on the ambient `$USER` there would
  have checked the cron-runner's permissions instead of the actual
  requester's.
- No synchronous/asynchronous size threshold: every apply request is
  queued, including small ones. A queued small change typically applies
  within a minute (next cron run) rather than instantly - see README.

## [1.2.2] - 2026-08-24

### Changed
- Removed 47 dead language strings across both `lang/en` and `lang/ru`:
  five orphaned `help*` strings left over from before the
  `<identifier>_help` convention, and 42 further strings confirmed unused
  by cross-checking every `get_string()`/`moodle_exception()` call in the
  codebase against the lang files (old naming duplicates superseded by
  strings actually in use, and remnants of a "reports" feature - most
  active user/course, totals - that was never built). Emptied section
  headers removed along with their content.
- Removed unused `courseids`/`stats_tests`/`stats_courses` hidden fields
  from `confirm_form` - posted but never read back by index.php's apply
  handler, which recomputes courses/stats itself from `categoryid`.

## [1.2.1] - 2026-08-24

### Changed
- Full pass against Moodle's official `moodle-cs` coding standard (the same
  one `moodle-plugin-ci phpcs` uses): 207 formatting issues auto-fixed,
  unnecessary `MOODLE_INTERNAL` checks removed from files with no side
  effects, missing class/method docblocks added. The plugin now runs clean
  with zero errors and zero warnings.

## [1.2.0] - 2026-08-24

### Added
- `local/examdates:preview` is now actually usable: a new **Exam dates
  preview** link appears in each course category's own admin menu (for users
  with `manage` or `preview` there), opening `preview.php` - a read-only
  before/after preview scoped to that category that never applies changes.
  Managers get a "Go to management page" link straight from the preview
  results, with the category pre-selected.
- `manager::get_courses_by_category()` now accepts a capability parameter, so
  it works for both `manage` and `preview` callers.

## [1.1.3] - 2026-08-24

### Fixed
- History page and rollback no longer crash with an uncaught exception when
  the underlying course has since been deleted; `get_course()` calls are now
  wrapped/replaced with safe batch lookups.

### Added
- Rollback is now wired up in the change-history page (was previously
  documented but had no UI): each row offers a "Roll back" action, restricted
  to the latest change per test and to users who can manage exam dates in
  that category, with a confirmation step.
- CLI tool `cli/update_exam_dates.php` for scripted/scheduled bulk updates,
  with a `--dryrun` preview mode.

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
