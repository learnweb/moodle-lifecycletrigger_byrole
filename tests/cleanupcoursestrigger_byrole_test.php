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
    protected function setUp() {
        // Recommended in Moodle docs to always include CFG.
        global $CFG;
        $this->resetAfterTest(true);
    }
    /**
     * Test the locallib function for valid courses.
     */
    public function test_lib_validcourse() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        $data = $generator->test_create_preparation();
        $mytrigger = new byrole();
        $donothandle = $mytrigger->check_course($data['teachercourse']);
        $this->assertEquals(trigger_response::next(), $donothandle);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['teachercourse']->id));
        $this->assertEquals(false, $exist);
    }
    /**
     * Test the locallib function for a invalid course that is recognized for the first time.
     */
    public function test_lib_norolecourse() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        $data = $generator->test_create_preparation();
        $mytrigger = new byrole();

        $dohandle = $mytrigger->check_course($data['norolecourse']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['norolecourse']->id));
        $this->assertEquals(trigger_response::next(), $dohandle);
        $this->assertEquals(true, $exist);
    }
    /**
     * Test the locallib function for a invalid course that is old enough to be triggered.
     */
    public function test_lib_norolefoundcourse() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        $data = $generator->test_create_preparation();
        $mytrigger = new byrole();

        $dotrigger = $mytrigger->check_course($data['norolefoundcourse']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['norolefoundcourse']->id));
        $this->assertEquals(trigger_response::trigger(), $dotrigger);
        $this->assertEquals(false, $exist);
    }
    /**
     * Test the locallib function for a course that was invalid and has a responsible person again.
     */
    public function test_lib_rolefoundagain() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        $data = $generator->test_create_preparation();
        $mytrigger = new byrole();

        $donothandle = $mytrigger->check_course($data['rolefoundagain']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['rolefoundagain']->id));
        $this->assertEquals(trigger_response::next(), $donothandle);
        $this->assertEquals(false, $exist);
    }
    /**
     * Test the locallib function in case the responsible person changed.
     */
    public function test_changevalidrole() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        $data = $generator->test_create_preparation();
        set_config('roles', 'manager', 'cleanupcoursestrigger_byrole');
        $mytrigger = new byrole_reset_roles();
        $mytrigger->reset_roles();
        $dohandle = $mytrigger->check_course($data['teachercourse']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['teachercourse']->id));
        $this->assertEquals(trigger_response::next(), $dohandle);
        $this->assertEquals(true, $exist);

        $donothandle = $mytrigger->check_course($data['managercourse']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['managercourse']->id));
        $this->assertEquals(trigger_response::next(), $donothandle);
        $this->assertEquals(false, $exist);
    }
    /**
     * Test the locallib function in case the responsible person changed.
     */
    public function test_changedelay() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        $data = $generator->test_create_preparation();
        set_config('delay', 32536000, 'cleanupcoursestrigger_byrole');
        $mytrigger = new byrole_reset_roles();
        // Course that was triggered beforehand is not handeled since the delay time is bigger.
        $donothandle = $mytrigger->check_course($data['norolefoundcourse']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['norolefoundcourse']->id));
        $this->assertEquals(trigger_response::next(), $donothandle);
        $this->assertEquals(true, $exist);

        // Really old courses are still triggered.
        $dotrigger = $mytrigger->check_course($data['norolefoundcourse2']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['norolefoundcourse2']->id));
        $this->assertEquals(trigger_response::trigger(), $dotrigger);
        $this->assertEquals(false, $exist);
    }
    /**
     * Test whether trigger::next() is thrown when no roles are defined.
     */
    public function test_noroles_exception() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('cleanupcoursestrigger_byrole');
        $data = $generator->test_create_preparation();
        set_config('roles', '', 'cleanupcoursestrigger_byrole');
        $mytrigger = new byrole_reset_roles();
        $mytrigger->reset_roles();
        // Although the course would be deleted it is triggered as next.
        $nothandle = $mytrigger->check_course($data['norolefoundcourse2']);
        $exist = $DB->record_exists('cleanupcoursestrigger_byrole', array('courseid' => $data['norolefoundcourse2']->id));
        $this->assertEquals(trigger_response::next(), $nothandle);
        $this->assertEquals(true, $exist);
    }
    /**
     * Method recommended by moodle to assure database and dataroot is reset.
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
     * Method recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', array()));
        $this->assertEquals(0, $DB->count_records('cleanupcoursestrigger_byrole', array()));
    }
}

/**
 * Class byrole_reset_roles minimal class to enable the reset of the static variable roles.
 * @package tool_cleanupcourses\trigger
 */
class byrole_reset_roles extends byrole {
    /**
     * Resets the static variable roles.
     */
    public function reset_roles() {
        self::$roles = null;
    }
}