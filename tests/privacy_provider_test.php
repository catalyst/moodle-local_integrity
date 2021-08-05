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
 * Tests for privacy provider.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity\tests;

use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\request\approved_contextlist;
use core_privacy\tests\provider_testcase;
use local_integrity\settings;
use local_integrity\privacy\provider;
use context_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for privacy provider.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_provider_test extends provider_testcase {

    /**
     * @var object Moodle course object.
     */
    private $course;

    /**
     * @var object Course module.
     */
    private $module;

    /**
     * @var object Moodle user object.
     */
    private $user;

    /**
     * @var \local_integrity\settings Test data.
     */
    private $settings;

    /**
     * Set up tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setup_test_data();

        parent::setUp();
    }

    /**
     * Setup the section settings and editing teacher as last modifier.
     */
    public function setup_test_data() {
        global $DB;

        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $this->course]);

        // Generate data as Admin.
        $settings = new settings();
        $settings->set('contextid', context_module::instance($module->cmid)->id);
        $settings->set('plugin', 'test');
        $settings->save();

        $this->module = $this->getDataGenerator()->create_module('assign', ['course' => $this->course]);
        $this->user = $this->getDataGenerator()->create_user(array('username' => 'teacher'));
        $role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, $role->id);

        // Generate data as a test user.
        $this->setUser($this->user);
        $this->settings = new settings();
        $this->settings->set('contextid', context_module::instance($this->module->cmid)->id);
        $this->settings->set('plugin', 'test');
        $this->settings->save();
    }

    /**
     * Test that the module context for a user who last modified the module is retrieved.
     */
    public function test_get_contexts_for_userid() {
        $contexts = provider::get_contexts_for_userid($this->user->id);
        $contextids = $contexts->get_contextids();
        $this->assertEquals(context_module::instance($this->module->cmid)->id, reset($contextids));
    }

    /**
     * That that no module context is found for a user who has not modified any section settings.
     */
    public function test_get_no_contexts_for_userid() {
        $user = $this->getDataGenerator()->create_user();
        $contexts = provider::get_contexts_for_userid($user->id);
        $contextids = $contexts->get_contextids();
        $this->assertEmpty($contextids);
    }

    /**
     * Test that user data is exported in format expected.
     */
    public function test_export_user_data() {
        $context = context_module::instance($this->module->cmid);
        $contextlist = provider::get_contexts_for_userid($this->user->id);

        $approvedcontextlist = new approved_contextlist(
            $this->user,
            'local_integrity',
            $contextlist->get_contextids()
        );

        writer::reset();
        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data());
        provider::export_user_data($approvedcontextlist);

        $index = '1'; // Get first data returned from the section settings table.
        $data = $writer->get_data([
            get_string('pluginname', 'local_integrity'),
            settings::TABLE,
            $index,
        ]);
        $this->assertNotEmpty($data);

        $index = '2'; // There should not be more than one instance with data.
        $data = $writer->get_data([
            get_string('pluginname', 'local_integrity'),
            settings::TABLE,
            $index,
        ]);
        $this->assertEmpty($data);
    }

    /**
     * Test that a userlist with course context is populated by usermodified user.
     */
    public function test_get_users_in_context() {
        // Create empty userlist with course context.
        $userlist = new userlist(context_module::instance($this->module->cmid), 'local_integrity');

        // Test that the userlist is populated with expected user/s.
        provider::get_users_in_context($userlist);
        $this->assertTrue(in_array($this->user->id, $userlist->get_userids()));
    }

    /**
     * Test that data is deleted for a list of users.
     */
    public function test_delete_data_for_users() {
        $this->assertNotEmpty(settings::get_records(['usermodified' => $this->user->id]));
        $this->assertEmpty(settings::get_records(['usermodified' => 0]));

        $approveduserlist = new approved_userlist(
            context_module::instance($this->module->cmid),
            'local_integrity',
            [$this->user->id]
        );

        provider::delete_data_for_users($approveduserlist);

        $this->assertEmpty(settings::get_records(['usermodified' => $this->user->id]));
        $this->assertNotEmpty(settings::get_records(['usermodified' => 0]));
    }

    /**
     * Test that data is deleted for a list of contexts.
     */
    public function test_delete_data_for_user() {
        $context = context_module::instance($this->module->cmid);
        $approvedcontextlist = new approved_contextlist(
            $this->user,
            'local_integrity',
            [$context->id]
        );

        // Test data exists.
        $this->assertNotEmpty(settings::get_records(['usermodified' => $this->user->id]));
        $this->assertEmpty(settings::get_records(['usermodified' => 0]));

        // Test data is deleted.
        provider::delete_data_for_user($approvedcontextlist);
        $this->assertEmpty(settings::get_records(['usermodified' => $this->user->id]));
        $this->assertNotEmpty(settings::get_records(['usermodified' => 0]));
    }

    /**
     * Test that data is deleted for all users a single context.
     */
    public function test_delete_data_for_all_users_in_context() {
        $context = context_module::instance($this->module->cmid);

        // Test data exists.
        $this->assertNotEmpty(settings::get_records(['usermodified' => $this->user->id]));
        $this->assertEmpty(settings::get_records(['usermodified' => 0]));

        // Test data is deleted.
        provider::delete_data_for_all_users_in_context($context);
        $this->assertEmpty(settings::get_records(['usermodified' => $this->user->id]));
        $this->assertNotEmpty(settings::get_records(['usermodified' => 0]));
    }
}
