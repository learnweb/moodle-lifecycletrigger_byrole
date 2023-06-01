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
 * Class to identify the courses to be deleted since they miss a person in charge.
 *
 * @package    lifecycletrigger_byrole
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_lifecycle\trigger;

use tool_lifecycle\local\manager\settings_manager;
use tool_lifecycle\local\response\trigger_response;
use tool_lifecycle\settings_type;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

/**
 * Class for processing courses if or if not roles are missing.
 */
class byrole extends base_automatic {

    /**
     * Extends the where clause by a statement which selects all entries of the byrole table,
     * which reached a specific age. That means they are longer than the max delay time without a responsible person.
     * Further, we update the byrole table in this function to refresh the records of the stored courses.
     * @param int $triggerid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_course_recordset_where($triggerid) {
        $this->update_courses($triggerid);
        $delay = settings_manager::get_settings($triggerid, settings_type::TRIGGER)['delay'];
        $maxtime = time() - $delay;

        $sql = "{course}.id in (SELECT DISTINCT courseid
              FROM {lifecycletrigger_byrole} WHERE triggerid = $triggerid AND timecreated < $maxtime)";
        return array($sql, array());
    }

    /**
     * Always triggers a course that got past the where clause.
     * @param \stdClass $course
     * @param int $triggerid id of the trigger instance
     * @return trigger_response one of next() or trigger()
     */
    public function check_course($course, $triggerid) {
            return trigger_response::trigger();
    }

    /**
     * Return the roles that were set in the config.
     * @param int $triggerid id of the trigger instance
     * @return array
     * @throws \coding_exception
     */
    private function get_roles($triggerid) {
        $roles = settings_manager::get_settings($triggerid, settings_type::TRIGGER)['roles'];
        if ($roles === "") {
            throw new \coding_exception('No Roles defined');
        } else {
            $roles = explode(",", $roles);
        }
        return $roles;
    }

    // MODIFICATION START
    /**
     * Compare category of course with a list of courses which have category enrolments.
     *
     * @param $caid int id of the category
     * @param $listcatenrols array list of categories with category enrolments
     * @return bool
     */
    public function checkParentCategoryEnrolment($caid, $listcatenrols) {
        global $DB;

        // Check if category is in the category list.
        // Break check if category enrolment is found.
        if (in_array($caid, $listcatenrols)) {
            return true;
        }

        // Look for parent category.
        $parent = $DB->get_field('course_categories', 'parent', ['id' => $caid]);

        // Keep looking if there is a parent category.
        if (0 != $parent) {
            mtrace("Checking next level parent category " . $parent);
            return $this->checkParentCategoryEnrolment($parent, $listcatenrols);
        } else {
            return false;
        }
    }
    // MODIFICATION END

    /**
     * Updates the current state of the courses
     * There are two cases:
     * 1. a course has no responsible user and no entry in the table, then the course should be inserted in the table,
     * 2. a course has an entry in the table but has a responsible person, then the course should be deleted from the table,
     * @param int $triggerid id of the trigger instance
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function update_courses($triggerid) {
        global $DB;
        $coursesintable = $DB->get_records('lifecycletrigger_byrole',
            array('triggerid' => $triggerid), '', 'courseid');

        $coursesintable = array_map(function($elem) {
            return $elem->courseid;
        }, $coursesintable);

        list($insql, $inparams) = $DB->get_in_or_equal($this->get_roles($triggerid), SQL_PARAMS_NAMED);

        $sql = "SELECT DISTINCT co.id
            FROM {course} co JOIN {context} cxt ON
              co.id = cxt.instanceid AND
              cxt.contextlevel = 50
            LEFT JOIN {role_assignments} ra ON ra.contextid = cxt.id AND
              ra.roleid {$insql}
            WHERE ra.id is null";
        $courseswithoutteacher = $DB->get_records_sql($sql, $inparams);

        $courseswithoutteacher = array_map(function($elem) {
            return $elem->id;
        }, $courseswithoutteacher);

        // MODIFICATION START
        // Get categories with category enrolments.
        $sqlcatenrols = "SELECT DISTINCT ca.id FROM {course_categories} ca JOIN {context} cxt
            ON ca.id = cxt.instanceid AND cxt.contextlevel = 40
            LEFT JOIN {role_assignments} ra ON ra.contextid = cxt.id
            AND ra.roleid {$insql} WHERE ra.id IS NOT NULL
            ORDER BY ca.id";
        $listcatenrols = $DB->get_records_sql($sqlcatenrols, $inparams);
        $listcatenrols = array_map(function($elem) {
            return $elem->id;
        }, $listcatenrols);

        // Look for courses with category enrolment instances.
        $sqlcatinstance = "SELECT DISTINCT co.id
            FROM {course} co JOIN mdl_enrol en ON co.id = en.courseid
            WHERE en.enrol='category' AND en.status = 0";
        $courseswithcatenrol = $DB->get_records_sql($sqlcatinstance, $inparams);

        $courseswithcatenrol = array_map(function($elem) {
            return $elem->id;
        }, $courseswithcatenrol);

        // Check nested parent category enrolments.
        $coursescatenrol = [];
        foreach ($courseswithcatenrol as $course) {
            // Look for category of course.
            $category = $DB->get_field('course', 'category', ['id' => $course]);
            $check = $this->checkParentCategoryEnrolment($category, $listcatenrols);
            if (false !== $check) {
                $coursescatenrol[] = $course;
            }
        }

        // Remove courses with category enrolments from course list.
        if (!empty($coursescatenrol)) {
            $courseswithoutteacher = array_diff($courseswithoutteacher, $coursescatenrol);
        }
        // MODIFICATION END

        // Insert new entries without the specified role assignments.

        $insertcourses = array_diff($courseswithoutteacher, $coursesintable);

        $records = array();
        foreach ($insertcourses as $courseid) {
            $dataobject = new \stdClass();
            $dataobject->courseid = $courseid;
            $dataobject->triggerid = $triggerid;
            $dataobject->timecreated = time();
            $records[] = $dataobject;
        }
        $DB->insert_records('lifecycletrigger_byrole', $records);

        // Delete old entries, which now have an assigment of the specified role, again.

        $deletecourses = array_diff($coursesintable, $courseswithoutteacher);

        list($insqltrigger, $inparamstrigger) = $DB->get_in_or_equal($triggerid, SQL_PARAMS_NAMED);
        if (!empty($deletecourses)) {
            list($insqlcourseids, $inparamscourseids) = $DB->get_in_or_equal($deletecourses, SQL_PARAMS_NAMED);

            $DB->delete_records_select('lifecycletrigger_byrole',
                "courseid {$insqlcourseids} AND triggerid {$insqltrigger}",
                array_merge($inparamscourseids, $inparamstrigger));
        }
    }

    /**
     * The return value should be equivalent with the name of the subplugin folder.
     * @return string technical name of the subplugin
     */
    public function get_subpluginname() {
        return 'byrole';
    }

    /**
     * Settings for the trigger.
     * @return array|instance_setting[]
     */
    public function instance_settings() {
        return array(
            new instance_setting('roles', PARAM_SEQUENCE),
            new instance_setting('delay', PARAM_INT),
        );
    }

    /**
     * Form for the instance.
     * @param \MoodleQuickForm $mform
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
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
