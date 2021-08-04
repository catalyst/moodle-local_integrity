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
 * Tests for lib functions.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity\tests;

use advanced_testcase;
use local_integrity\mod_settings;
use local_integrity\plugininfo\integritystmt;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for lib functions.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends advanced_testcase {

    /**
     * Test modifying an activity form standard elements.
     */
    public function test_coursemodule_standard_elements() {
        global $PAGE, $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        foreach (integritystmt::get_enabled_plugins() as $name) {
            $modmoodleform = "$CFG->dirroot/mod/$name/mod_form.php";

            // Skip this test as the plugins changing a mod form should match an activity name.
            if (!file_exists($modmoodleform)) {
                $this->markTestSkipped();
            }

            require_once($modmoodleform);
            $formclass = "\mod_{$name}_mod_form";

            $module = $this->getDataGenerator()->create_module($name, ['course' => $course->id]);

            [$course, $cm] = get_course_and_cm_from_cmid($module->cmid);
            $PAGE->set_course($course);

            list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($cm, $course);
            // Remove availability conditions to prevent issues with getting form data.
            $data->availabilityconditionsjson = '';

            $submitdata = clone $data;
            $submitdata->id = $cm->id;

            // Mock submitting the form so we can check it's data.
            $mform = new $formclass($data, $cw->section, $cm, $course);
            $mform::mock_submit((array) $submitdata, []);

            $mform = new $formclass($data, $cw->section, $cm, $course);

            $actual = $mform->get_data();
            $this->assertObjectHasAttribute('integrity_enabled', $actual);
        }
    }

    /**
     * Test modifying an activity form standard elements if no mod name provided.
     */
    public function test_coursemodule_standard_elements_no_modname() {
        global $PAGE, $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        foreach (integritystmt::get_enabled_plugins() as $name) {
            $modmoodleform = "$CFG->dirroot/mod/$name/mod_form.php";

            // Skip this test as the plugins changing a mod form should match an activity name.
            if (!file_exists($modmoodleform)) {
                $this->markTestSkipped();
            }

            require_once($modmoodleform);
            $formclass = "\mod_{$name}_mod_form";

            $module = $this->getDataGenerator()->create_module($name, ['course' => $course->id]);

            [$course, $cm] = get_course_and_cm_from_cmid($module->cmid);
            $PAGE->set_course($course);

            // Mock data for new course module being created.
            $data = new \stdClass();
            $data->name = 'Without course module';
            $data->visible = 1;
            $data->course = $course->id;
            $data->section = 0;
            $data->instance = '';
            $data->coursemodule = null;
            $data->cmidnumber = '';
            // Remove availability conditions to prevent issues with getting form data.
            $data->availabilityconditionsjson = '';

            // Mock submitting the form so we can check it's data.
            $mform = new $formclass($data, $data->section, $data->coursemodule, $course);
            $mform::mock_submit((array) $data, []);

            $mform = new $formclass($data, $data->section, $data->coursemodule, $course);

            $actual = $mform->get_data();
            $this->assertObjectNotHasAttribute('integrity_enabled', $actual);
        }
    }

    /**
     * Test submission of an activity form.
     */
    public function test_coursemodule_edit_post_actions() {
        global $PAGE, $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        foreach (integritystmt::get_enabled_plugins() as $name) {
            $modmoodleform = "$CFG->dirroot/mod/$name/mod_form.php";

            // Skip this test as the plugins changing a mod form should match an activity name.
            if (!file_exists($modmoodleform)) {
                $this->markTestSkipped();
            }

            $module = $this->getDataGenerator()->create_module($name, ['course' => $course->id]);

            [$course, $cm] = get_course_and_cm_from_cmid($module->cmid);
            $PAGE->set_course($course);

            list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($cm, $course);
            $data->availabilityconditionsjson = '';
            $data->integrity_enabled = 1;

            local_integrity_coursemodule_edit_post_actions($data, $course);
            $this->assertNotEmpty(mod_settings::get_record(['cmid' => $cm->id]));
        }
    }

}
