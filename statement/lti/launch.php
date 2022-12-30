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
 * This page is required to be able to show a statement before redirecting to an LTI page.
 *
 * @see integritystmt_lti_after_require_login
 * @see integritystmt_lti\statement::get_agree_url
 *
 * @package     integritystmt_lti
 * @copyright   2022 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../config.php");

$id = required_param('id', PARAM_INT); // Course Module ID.
$triggerview = optional_param('triggerview', 1, PARAM_BOOL);

$cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
$lti = $DB->get_record('lti', array('id' => $cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/lti:view', $context);

$PAGE->set_cm($cm, $course);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new moodle_url('/local/integrity/statement/lti/launch.php', ['id' => $cm->id, 'triggerview' => $triggerview]));

$pagetitle = strip_tags($course->shortname.': '.format_string($lti->name));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('lti:redirecting', 'integritystmt_lti'));
echo $OUTPUT->footer();
