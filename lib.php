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
 * @package    tool_lifecycle_trigger
 * @subpackage byrole
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_lifecycle\trigger;

use tool_lifecycle\manager\settings_manager;
use tool_lifecycle\response\trigger_response;
use tool_lifecycle\settings_type;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

class byrole extends base_automatic {
    /** @var $roles array Saves all roles that are marked as in charge */
    protected static $roles = null;

    /**
     * Checks the given course object and returns next() when the course has a responsible person and trigger() in case
     * the course has no responsible person and has been noted without a responsible person for a determined period of time.
     * The time period is defined in the admin settings.
     * Excluding courses is not necessary.
     * @param $course
     * @param $triggerid int id of the trigger instance
     * @return trigger_response one of next() or trigger()
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function check_course($course, $triggerid) {
        // Checks whether role is represented in course.
        $hasuserincharge = $this->check_course_has_role($course->id, $triggerid);

        // When an exception was thrown the course is not handled.
        if ($hasuserincharge === null) {
            return trigger_response::next();
        }
        $trigger = $this->handle_course($hasuserincharge, $course->id, $triggerid);
        if ($trigger) {
            return trigger_response::trigger();
        }
        return trigger_response::next();
    }

    /**
     * Checks whether a specific course has a responsible person.
     * This check is based on roles. The responsible roles are fixed in the admin settings.
     * @param $courseid
     * @param $triggerid int id of the trigger instance
     * @return boolean | null
     */
    private function check_course_has_role($courseid, $triggerid) {
        // Gets roles from the settings.
        try {
            $roles = $this->get_roles($triggerid);
        } catch (\coding_exception $e) {
            // Writhe in Log without writing it repeatedly.
            return null;
        }
        $context = \context_course::instance($courseid);
        // Returns all roles used in context and in parent context. Therefore be carefully with global roles!
        $courseroles = get_roles_used_in_context($context);

        // Most likely case: role(s) were defined. get_roles() always returns an array or throws an exception.
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
     * @param $triggerid int id of the trigger instance
     * @return array
     * @throws \coding_exception
     */
    private function get_roles($triggerid) {
        if (self::$roles === null) {
            $roles = settings_manager::get_settings($triggerid, settings_type::TRIGGER)['roles'];
            if ($roles === "") {
                throw new \coding_exception('No Roles defined');
            } else {
                self::$roles = explode(",", $roles);
            }
        }
        return self::$roles;
    }

    /**
     * Handles the current course
     * There are three cases:
     * 1. the course has no responsible user and no entry in the table the course should be inserted in the table,
     * 2. the course has an entry in the table but has a responsible person, the course should be deleted from the table,
     * 3. the course does not have a responsible person and is already in the table, it has to be checked how long
     * the course is in the table and when the period of time is exceeded the course is marked as to delete.
     * In case the course is not in the table and has a responsible person nothing has to be done.
     * @param $hasuserincharge boolean
     * @param $courseid integer
     * @param $triggerid int id of the trigger instance
     * @return boolean
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function handle_course($hasuserincharge, $courseid, $triggerid) {
        global $DB;
        $intable = $DB->record_exists('lifecycletrigger_byrole', array('courseid' => $courseid));
        // First case of function description.
        if ($intable === false && $hasuserincharge === false) {
            $dataobject = new \stdClass();
            $dataobject->courseid = $courseid;
            $dataobject->timestamp = time();
            $DB->insert_record('lifecycletrigger_byrole', $dataobject);
            return false;
            // Second case of function description.
        } else if ($intable && $hasuserincharge) {
            // Second case of function description.
            $DB->delete_records('lifecycletrigger_byrole', array('courseid' => $courseid));
            return false;
            // Third case of the function description.
        } else if ($intable && !$hasuserincharge) {
            $delay = settings_manager::get_settings($triggerid, settings_type::TRIGGER)['delay'];
            $timecreated = $DB->get_record('lifecycletrigger_byrole', array('courseid' => $courseid), 'timestamp');
            $now = time();
            $difference = $now - $timecreated->timestamp;
            // Checks how long the course has been in the table and deletes the table entry and the course.
            if ($difference > $delay) {
                $DB->delete_records('lifecycletrigger_byrole', array('courseid' => $courseid));
                return true;
            }
        }
        return false;
    }

    /**
     * The return value should be equivalent with the name of the subplugin folder.
     * @return string technical name of the subplugin
     */
    public function get_subpluginname() {
        return 'byrole';
    }

    public function instance_settings() {
        return array(
            new instance_setting('roles', PARAM_SEQUENCE),
            new instance_setting('delay', PARAM_INT),
        );
    }

    public function extend_add_instance_form_definition($mform) {
        global $DB;
        $allroles = $DB->get_records('role', null, 'sortorder DESC');

        $rolenames = array();
        foreach ($allroles as $role) {
            $rolenames[$role->id] = empty($role->name) ? $role->shortname : $role->name;
        }
        $options = array(
            'multiple' => true,
        );
        $mform->addElement('autocomplete', 'roles',
            get_string('responsibleroles', 'lifecycletrigger_byrole'),
            $rolenames, $options);
        $mform->addHelpButton('roles', 'responsibleroles', 'lifecycletrigger_byrole');
        $mform->setType('roles', PARAM_SEQUENCE);
        $mform->addRule('roles', 'Test', 'required');

        $elementname = 'delay';
        $mform->addElement('duration', $elementname, get_string('delay', 'lifecycletrigger_byrole'));
        $mform->addHelpButton('delay', 'delay', 'lifecycletrigger_byrole');
        $mform->setType($elementname, PARAM_INT);
    }
}