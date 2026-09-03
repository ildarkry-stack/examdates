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
 * Adhoc task that applies a confirmed bulk assessment-date change.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdates\task;

/**
 * Runs manager::apply_updates() in the background instead of inline in the
 * web request. A category with hundreds or thousands of courses can take
 * far longer than a typical PHP execution-time limit, and a failure partway
 * through a synchronous request leaves an unrecoverable, half-applied state
 * with nothing shown to the person who clicked "Apply" - this sidesteps
 * both problems. Course resolution and permission checks are re-run here
 * against the requesting user's id (there is no "current" web user in a
 * background task), not just carried over from when the task was queued.
 */
class apply_updates_task extends \core\task\adhoc_task {
    /**
     * Return the task's localised name (shown on the adhoc tasks admin page).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_apply_updates', 'local_examdates');
    }

    /**
     * Resolve the courses, apply the change, and message the requesting
     * user with the outcome - they're not watching a page for this.
     */
    public function execute() {
        $data = $this->get_custom_data();
        $manager = new \local_examdates\manager();

        try {
            $totalcourses = $manager->count_courses_by_category(
                $data->categoryid,
                !empty($data->include_sub),
                'local/examdates:manage',
                $data->userid
            );
        } catch (\required_capability_exception $e) {
            $this->notify_user($data->userid, get_string('error_nopermission', 'local_examdates'));
            return;
        }

        if ($totalcourses === 0) {
            $this->notify_user($data->userid, get_string('no_courses_found', 'local_examdates'));
            return;
        }

        // Process the complete category scope in bounded chunks. This keeps the
        // adhoc task's memory use stable even for thousands of courses while
        // retaining one logical batch id and one aggregate Moodle event.
        $batchid = $manager->create_batch_id();
        $updatedcount = 0;
        $errorcount = 0;
        $changedcoursecount = 0;

        for ($offset = 0; $offset < $totalcourses; $offset += \local_examdates\manager::PROCESS_BATCH_SIZE) {
            $courses = $manager->get_courses_by_category(
                $data->categoryid,
                !empty($data->include_sub),
                'local/examdates:manage',
                $data->userid,
                $offset,
                \local_examdates\manager::PROCESS_BATCH_SIZE
            );

            if (empty($courses)) {
                break;
            }

            $result = $manager->apply_updates($courses, $data, $data->userid, $batchid, false);
            $updatedcount += count($result['updated']);
            $errorcount += count($result['errors']);
            $changedcoursecount += count(array_unique(array_column($result['updated'], 'courseid')));
        }

        $manager->trigger_batch_event(
            $data->userid,
            $batchid,
            $updatedcount,
            (int)$data->categoryid,
            $changedcoursecount
        );

        $message = get_string('changes_applied_detailed', 'local_examdates', (object)[
            'items' => $updatedcount,
            'courses' => $changedcoursecount,
        ]);

        if ($errorcount > 0) {
            $message .= ' ' . get_string('errors', 'local_examdates') . ': ' . $errorcount;
        }

        $this->notify_user($data->userid, $message);
    }

    /**
     * Message the requesting user with the outcome, since a background task
     * has no page to render a result on.
     *
     * @param int $userid Who to notify
     * @param string $summary Plain-text outcome summary
     */
    private function notify_user($userid, $summary) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$user) {
            return;
        }

        $message = new \core\message\message();
        $message->component        = 'local_examdates';
        $message->name              = 'apply_complete';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = $user;
        $message->subject           = get_string('apply_complete_subject', 'local_examdates');
        $message->fullmessage       = $summary;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = '';
        $message->smallmessage      = $summary;
        $message->notification      = 1;
        $message->contexturl        = new \moodle_url('/local/examdates/history.php');
        $message->contexturlname    = get_string('history_title', 'local_examdates');

        message_send($message);
    }
}
