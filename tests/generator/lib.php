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
 * Generator for the cleanupcoursestrigger_byrole testcase
 * @category   test
 * @package    tool_cleanupcourses
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
/**
 * Generator class for the cleanupcoursestrigger_byrole.
 *
 * @category   test
 * @package    tool_cleanupcourses
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanupcoursestrigger_byrole_generator extends testing_data_generator {
    /**
     * Creates data to test the trigger subplugin cleanupcoursestrigger_byrole.
     */
    public function test_create_preparation () {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();
        $data = array();
        $validcourse = $generator->create_course(array('name' => 'validcourse'));

        // Creates 2 Users, enroles them in validcourse.
        $user = $generator->create_user();
        $data['user1'] = $user;
        $generator->enrol_user($user->id, $validcourse->id, 4);

        $data['user2'] = $user;
        $generator->enrol_user($user->id, $validcourse->id, 5);

        $data['validcourse'] = $validcourse;

        // Create a course without valid role.
        $norolecourse = $generator->create_course(array('name' => 'norolecourse'));

        $data['norolecourse'] = $norolecourse;

        // Create a already in table without valid role and old.
        $norolefoundcourse = $generator->create_course(array('name' => 'norolefoundcourse'));
        $user3 = $generator->create_user();
        $data['user3'] = $user3;
        $generator->enrol_user($user3->id, $norolefoundcourse->id, 5);
        // Writhes course in table and enrol one student.
        $dataobject = new \stdClass();
        $dataobject->id = $norolefoundcourse->id;
        $dataobject->timestamp = time() - 31536000;
        $DB->insert_record_raw('cleanupcoursestrigger_byrole', $dataobject, true, false, true);
        $data['norolefoundcourse'] = $norolefoundcourse;

        // Create a already in table with valid role and old.
        $rolefoundagain = $generator->create_course(array('name' => 'rolefoundagain'));
        $user4 = $generator->create_user();
        $data['user4'] = $user4;
        // Writhes course in table and enrol one teacher.
        $generator->enrol_user($user4->id, $rolefoundagain->id, 4);
        $dataobject = new \stdClass();
        $dataobject->id = $rolefoundagain->id;
        $dataobject->timestamp = time() - 31536000;
        $DB->insert_record_raw('cleanupcoursestrigger_byrole', $dataobject, true, false, true);
        $data['rolefoundagain'] = $rolefoundagain;
        return $data;
    }

}