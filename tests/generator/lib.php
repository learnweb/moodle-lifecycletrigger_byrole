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

        return $data;
    }

}