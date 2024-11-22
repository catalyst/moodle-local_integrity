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

namespace local_integrity;

use advanced_testcase;
use core_component;

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
     * Set up tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        parent::setUp();
    }

    /**
     * A helper method to check if the module have generator.
     *
     * @param string $name Name of the statement.
     *
     * @return bool
     */
    protected function has_data_generator(string $name): bool {
        $dir = core_component::get_component_directory('mod_'. $name);
        $lib = $dir . '/tests/generator/lib.php';

        if (!$dir || !is_readable($lib)) {
            return false;
        }

        return true;
    }

    /**
     * A helper method to find out the class of the mod form.
     *
     * @param string $name Name of the statement.
     *
     * @return string|null
     */
    protected function get_form_class_name(string $name): ?string {
        global $CFG;

        $modmoodleform = "$CFG->dirroot/mod/$name/mod_form.php";

        // Skip this test as the plugins changing a mod form should match an activity name.
        if (!file_exists($modmoodleform)) {
            return null;
        }

        require_once($modmoodleform);

        return "\mod_{$name}_mod_form";
    }

    /**
     * Test modifying an activity form standard elements.
     * @covers \local_integrity_coursemodule_standard_elements
     */
    public function test_coursemodule_standard_elements() {
        global $PAGE;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $PAGE->set_course($course);

        foreach (statement_factory::get_statements() as $name => $statement) {
            if (!$this->has_data_generator($name)) {
                continue;
            }

            if (!$formclass = $this->get_form_class_name($name)) {
                continue;
            }

            $module = $this->getDataGenerator()->create_module($name, ['course' => $course->id]);
            [$course, $cm] = get_course_and_cm_from_cmid($module->cmid);
            list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($cm, $course);

            $form = new \MoodleQuickForm('test', 'post', '');
            $modform = new $formclass($data, $cw->section, $cm, $course);

            $this->assertFalse($form->elementExists('integrity_enabled'));

            local_integrity_coursemodule_standard_elements($modform, $form);
            $this->assertTrue($form->elementExists('integrity_enabled'));
        }
    }

    /**
     * Test modifying an activity form standard elements if no mod name provided.
     * @covers \local_integrity_coursemodule_standard_elements
     */
    public function test_coursemodule_standard_elements_no_modname() {
        global $PAGE;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $PAGE->set_course($course);

        foreach (statement_factory::get_statements() as $name => $statement) {
            if (!$this->has_data_generator($name)) {
                continue;
            }

            if (!$formclass = $this->get_form_class_name($name)) {
                continue;
            }

            // Mock data for new course module being created.
            $data = new \stdClass();
            $data->name = 'Without course module';
            $data->visible = 1;
            $data->course = $course->id;
            $data->section = 0;
            $data->instance = '';
            $data->coursemodule = null;
            $data->cmidnumber = '';

            $form = new \MoodleQuickForm('test', 'post', '');
            $modform = new $formclass($data, $data->section, $data->coursemodule, $course);

            $this->assertFalse($form->elementExists('integrity_enabled'));

            local_integrity_coursemodule_standard_elements($modform, $form);
            $this->assertFalse($form->elementExists('integrity_enabled'));
        }
    }

    /**
     * Test submission of an activity form.
     * @covers \local_integrity_coursemodule_edit_post_actions
     */
    public function test_coursemodule_edit_post_actions() {
        global $PAGE;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        foreach (statement_factory::get_statements() as $name => $statement) {
            if (!$this->has_data_generator($name)) {
                continue;
            }

            if (!$formclass = $this->get_form_class_name($name)) {
                continue;
            }

            $module = $this->getDataGenerator()->create_module($name, ['course' => $course->id]);

            [$course, $cm] = get_course_and_cm_from_cmid($module->cmid);
            $PAGE->set_course($course);
            list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($cm, $course);

            $data->integrity_enabled = 0;
            local_integrity_coursemodule_edit_post_actions($data, $course);
            $this->assertFalse(settings::get_record(['contextid' => $context->id, 'enabled' => 1]));
            $this->assertNotEmpty(settings::get_record(['contextid' => $context->id, 'enabled' => 0]));

            $data->integrity_enabled = 1;
            local_integrity_coursemodule_edit_post_actions($data, $course);
            $this->assertNotEmpty(settings::get_record(['contextid' => $context->id, 'enabled' => 1]));
            $this->assertFalse(settings::get_record(['contextid' => $context->id, 'enabled' => 0]));
        }
    }

    /**
     * Check that our hook is called when an activity is deleted.
     * @covers \local_integrity_pre_course_module_delete
     */
    public function test_pre_course_module_delete_hook() {
        $this->assertCount(0, settings::get_records());

        $course = $this->getDataGenerator()->create_course();
        $module1 = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $module2 = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $settings = new settings();
        $settings->set('contextid', \context_module::instance($module1->cmid)->id);
        $settings->set('plugin', 'test');
        $settings->save();

        $this->assertCount(1, settings::get_records());

        $settings = new settings();
        $settings->set('contextid', \context_module::instance($module2->cmid)->id);
        $settings->set('plugin', 'test');
        $settings->save();
        $this->assertCount(2, settings::get_records());

        course_delete_module($module1->cmid);
        $this->assertCount(1, settings::get_records());

        course_delete_module($module2->cmid);
        $this->assertCount(0, settings::get_records());
    }

}
