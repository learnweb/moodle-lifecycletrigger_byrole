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
 * Class to identify the courses to be deleted since they miss a
 * a person in charge.
 *
 * @package tool_cleanupcourses
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupcourses\trigger;

use tool_cleanupcourses\response\trigger_response;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');


class byrole implements base {
    /**
     * Checks the given course object and returns next() when the course has a responsible person and trigger() in case
     * the course has no responsible person. Exclude is not necessary.
     * @param $course
     * @return trigger_response one of next() or trigger()
     */
    public function check_course($course) {
        // Checks whether tole is in course.
        $hasrole = $this->check_course_has_role($course->id);

        $trigger = $this->handle_course($hasrole, $course->id);
        if ($trigger) {
            return trigger_response::trigger();
        }
        return trigger_response::next();
    }

    /** Checks whether a specific course has one of the roles defined in the trigger plugin config.
     * @param $courseid
     * @return bool
     */
    private function check_course_has_role($courseid) {
        global $CFG;

        // Gets roles and time period until deletion from the settings.
        $roles = $this->get_roles();
        // Get the context.
        $context = \context_course::instance($courseid);
        $courseroles = get_roles_used_in_context($context);

        // Most likely case: role(s) were defined.
        if (is_array($roles)) {
            foreach ($roles as $role) {
                foreach ($courseroles as $courserole) {
                    if ($courserole->shortname === $role) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Return the roles that were set in the config.
     * @return array|null
     * @throws \coding_exception
     */
    private function get_roles() {
        global $CFG;
        // Static to optimize the call, and prevent repetition of explode.
        static $roles = null;
        if ($roles === null) {
            $roles = $CFG->cleanupcoursestrigger_byrole_roles;
            if ($roles === "") {
                throw new \coding_exception('No Roles defined');
            } else {
                $roles = explode(",", $roles);
            }
        }
        return $roles;
    }

    /**
     * Handles the current course
     * There are three cases:
     * 1. the course has no role and no entry in the table the course should be inserted in the table
     * 2. the course has an entry in the table but has a responsible person, the course should be deleted from the table
     * 3. the course does not have a responsible person and is already in the table, it has to be checked how long
     * the course is in the table and eventually the course is marked as to delete.
     * @param $hasrole
     * @param $courseid
     * @return bool
     */
    private function handle_course($hasrole, $courseid) {
        global $DB, $CFG;
        $intable = $DB->record_exists('cleanupcoursestrigger_byrole', array('id' => $courseid));
        if ($intable === false && $hasrole === false) {
            $dataobject = new \stdClass();
            $dataobject->id = $courseid;
            $dataobject->timestamp = time();
            $DB->insert_record_raw('cleanupcoursestrigger_byrole', $dataobject, true, false, true);
            return false;
        } else if ($intable === true) {
            if ($hasrole) {
                $DB->delete_records('cleanupcoursestrigger_byrole', array('id' => $courseid));
                return false;
            } else {
                $delay = $CFG->cleanupcoursestrigger_byrole_delay;
                $timecreated = $DB->get_record('cleanupcoursestrigger_byrole', array('id' => $courseid), 'timestamp');

                $now = time();
                $difference = $now - $timecreated->timestamp;
                // Checks how long the course has been in the table and deletes the course.
                if ($difference > $delay) {
                    $DB->delete_records('cleanupcoursestrigger_byrole', array('id' => $courseid));
                    return true;
                }
            }
        }
        return false;
    }
}