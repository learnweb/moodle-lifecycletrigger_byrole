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
 * The class contains a test script for the trigger subplugin byrole
 *
 * @package tool_cleanupcourses
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cleanupcourses\trigger;

use tool_cleanupcourses\response\trigger_response;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

/**
 * Class cleanupcoursestrigger_byrole_testcase
 * @category   test
 * @package    tool_cleanupcourses
 * @group      cleanupcourses_trigger_byrole
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanupcoursestrigger_byrole_testcase extends \advanced_testcase {
    /**
     * Set up environment for phpunit test.
     * @return mixed data for test
     */
    protected function set_up() {
        // Recommended in Moodle docs to always include CFG.
        global $CFG;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        set_config('cleanupcoursestrigger_byrole_roles', 'teacher');

        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }

    /**
     * Function to test the locallib function for valid courses.
     */
    public function test_lib_validcourse() {
        global $DB;
        $data = $this->set_up();
        $mytrigger = new byrole();
        $donothandle = $mytrigger->check_course($data['validcourse']);
        $this->assertEquals(trigger_response::next(), $donothandle);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('id' => $data['validcourse']->id));
        $this->assertEquals(false, $exist);
    }

    /**
     * Function to test the locallib function for a invalid course that is recognized for the first time.
     */
    public function test_lib_norolecourse() {
        global $DB;
        $data = $this->set_up();
        $mytrigger = new byrole();

        $norolehandler = $mytrigger->check_course($data['norolecourse']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('id' => $data['norolecourse']->id));
        $this->assertEquals(trigger_response::next(), $norolehandler);
        $this->assertEquals(true, $exist);
    }
    /**
     * Function to test the locallib function for a invalid course that is old enough to be triggered.
     */
    public function test_lib_norolefoundcourse() {
        global $DB;
        $data = $this->set_up();
        $mytrigger = new byrole();

        $dotrigger = $mytrigger->check_course($data['norolefoundcourse']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('id' => $data['norolefoundcourse']->id));
        $this->assertEquals(trigger_response::trigger(), $dotrigger);
        $this->assertEquals(false, $exist);
    }
    /**
     * Test the locallib function for a course that was invalid and has a responsible person again.
     */
    public function test_lib_rolefoundagain() {
        global $DB;
        $data = $this->set_up();
        $mytrigger = new byrole();

        $dotrigger = $mytrigger->check_course($data['rolefoundagain']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('id' => $data['rolefoundagain']->id));
        $this->assertEquals(trigger_response::next(), $dotrigger);
        $this->assertEquals(false, $exist);
    }
    /**
     * Methodes recommended by moodle to assure database and dataroot is reset.
     */
    public function test_deleting() {
        global $DB;
        $this->resetAfterTest(true);
        $DB->delete_records('user');
        $DB->delete_records('cleanupcoursestrigger_byrole');
        $this->assertEmpty($DB->get_records('user'));
        $this->assertEmpty($DB->get_records('cleanupcoursestrigger_byrole'));
    }
    /**
     * Methodes recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', array()));
        $this->assertEquals(0, $DB->count_records('cleanupcoursestrigger_byrole', array()));
    }
}