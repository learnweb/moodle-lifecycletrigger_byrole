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
 * Settings for lost
 *
 * @package tool_cleanupcourses_trigger
 * @subpackage lost
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

// Time until a abandoned course is deleted default is 4 weeks.
$settings->add(new admin_setting_configduration('cleanupcoursestrigger_lost_delay',
    get_string('delay', 'cleanupcoursestrigger_lost'),
    get_string('delay', 'cleanupcoursestrigger_lost'), 2419200));
$roles = get_all_roles();
$choices = array();
foreach ($roles as $role) {
    $choices[$role->shortname] = $role->shortname;
}
$settings->add(new admin_setting_configmulticheckbox('cleanupcoursestrigger_lost_roles', get_string('responsibleroles',
    'cleanupcoursestrigger_lost'), get_string('explanationroles', 'cleanupcoursestrigger_lost'),
    array('teacher' => 'teacher'), $choices));
