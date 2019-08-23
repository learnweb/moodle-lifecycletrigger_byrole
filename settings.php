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
 * Settings for byrole
 *
 * @package tool_lifecycle_trigger
 * @subpackage byrole
 * @copyright  2017 Tobias Reischmann WWU Nina Herrmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

// Add setting for time period until a abandoned course is deleted. The default is 4 weeks.
$settings->add(new admin_setting_configduration('lifecycletrigger_byrole/delay',
    get_string('delay', 'lifecycletrigger_byrole'),
    get_string('delay', 'lifecycletrigger_byrole'), 2419200));
$roles = get_all_roles();
$choices = array();
foreach ($roles as $role) {
    $choices[$role->shortname] = $role->shortname;
}
// Add setting for roles that are responsible for a course. The default is teacher and editingteacher.

$settings->add(new admin_setting_configmulticheckbox('lifecycletrigger_byrole/roles', get_string('responsibleroles',
    'lifecycletrigger_byrole'), get_string('explanationroles', 'lifecycletrigger_byrole'),
    array('teacher' => 'teacher', 'editingteacher' => 'editingteacher'), $choices));
