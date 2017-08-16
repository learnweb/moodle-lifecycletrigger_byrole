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
        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }

    /**
     * Function to test the locallib functions.
     */
    public function test_lib() {
        global $DB, $CFG;
        set_config('cleanupcoursestrigger_byrole_roles', 'teacher');
        $data = $this->set_up();
        $mytrigger = new byrole();
        $donothandle = $mytrigger->check_course($data['validcourse']);
        $this->assertEquals(trigger_response::next(), $donothandle);
    }
}