<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_integrity
 * @category    string
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Academic integrity';
$string['cachedef_settings'] = 'Statement settings';
$string['cachedef_userdata'] = 'Statement agreement data';
$string['modform:header'] = 'Academic integrity';
$string['integrity:agreestatements'] = 'Agree statements on behalf of others';
$string['modform:header'] = 'Academic integrity';
$string['modform:enabled'] = 'Display academic integrity notice?';
$string['preview'] = 'Preview';
$string['privacy:metadata:local_integrity_settings'] = 'Details of Integrity plugin settings.';
$string['privacy:metadata:local_integrity_settings:contextid'] = 'Context ID of the settings.';
$string['privacy:metadata:local_integrity_settings:usermodified'] = 'ID of user who last created or modified the settings.';
$string['privacy:metadata:local_integrity_settings:timecreated'] = 'Unix time that the settings were created.';
$string['privacy:metadata:local_integrity_settings:timemodified'] = 'Unix time that the setting were modified.';
$string['privacy:metadata:local_integrity_userdata'] = 'Details of Integrity User data.';
$string['privacy:metadata:local_integrity_userdata:userid'] = 'User ID of.';
$string['privacy:metadata:local_integrity_userdata:plugin'] = 'Plugin name.';
$string['privacy:metadata:local_integrity_userdata:contextids'] = 'A list of user data';
$string['settings:default_enabled'] = 'Enabled by default';
$string['settings:default_enabled_description'] = 'If yes, the notice will be displayed by default, irrespective of wether the staff member is permitted or not to have control of setting.';
$string['settings:lastupdatedated'] = 'Last updated date on {$a}';
$string['settings:notice'] = 'Notice text';
$string['settings:notice_description'] = 'This text will be displayed to the users.';
$string['statement:header'] = 'Academic integrity notice';
$string['statement:save'] = 'Agree';
$string['statement:cancel'] = 'Cancel';
$string['statement:agree'] = 'I have read and agree to the above statement.';
$string['statement:agreementrequired'] = 'You  must agree to continue.';
