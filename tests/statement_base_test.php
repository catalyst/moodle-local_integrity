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
 * Tests for statement base class.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity\tests;

use advanced_testcase;
use local_integrity\settings;
use local_integrity\statement_base;
use local_integrity\statement_factory;
use local_integrity\userdata_default;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for statement base class.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class statement_base_test extends advanced_testcase {

    /**
     * Returns a test instance of the statement class.
     *
     * @param string $name Name of the test statement object.
     * @return \local_integrity\statement_base|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function get_test_statement(string $name) {
        $stub = $this->getMockForAbstractClass(statement_base::class, [$name]);
        $stub->expects($this->any())
            ->method('get_display_urls')
            ->will($this->returnValue(['/test/index.php', '/test/edit.php']));

        return $stub;
    }

    /**
     * Test get name.
     */
    public function test_get_name() {
        $statement = $this->get_test_statement('test');
        $this->assertSame('test', $statement->get_name());
    }

    /**
     * Test get plugin name.
     */
    public function test_get_plugin_name() {
        $statement = $this->get_test_statement('test');
        $this->assertSame('integritystmt_test', $statement->get_plugin_name());
    }

    /**
     * Test get notice.
     */
    public function test_get_notice() {
        $this->resetAfterTest();
        $statement = $this->get_test_statement('test');

        $this->assertEmpty($statement->get_notice());

        set_config('notice', 'Test text', 'integritystmt_test');
        $this->assertEquals('Test text', $statement->get_notice());
    }

    /**
     * Test get get_default_enabled.
     */
    public function test_get_default_enabled() {
        $this->resetAfterTest();
        $statement = $this->get_test_statement('test');

        $this->assertEquals(0, $statement->get_default_enabled());

        set_config('default_enabled', 1, 'integritystmt_test');
        $this->assertEquals(1, $statement->get_default_enabled());

        set_config('default_enabled', 'not integer', 'integritystmt_test');
        $this->assertEquals(0, $statement->get_default_enabled());
    }

    /**
     * Test decline URL.
     */
    public function test_get_decline_url() {
        global $COURSE;
        $this->resetAfterTest();

        $statement = $this->get_test_statement('test');

        $this->assertEquals('https://www.example.com/moodle/course/view.php?id=1', $statement->get_decline_url());

        $COURSE->id = 15;
        $this->assertEquals('https://www.example.com/moodle/course/view.php?id=15', $statement->get_decline_url());

        $COURSE->id = 0;
        $this->assertEquals('', $statement->get_decline_url());

        unset($COURSE->id);
        $this->assertEquals('', $statement->get_decline_url());

        unset($COURSE);
        $this->assertEquals('', $statement->get_decline_url());
    }

    /**
     * Test get getting last updated date for settings.
     */
    public function test_get_setting_last_updated_date() {
        global $DB;

        $this->resetAfterTest();
        $statement = $this->get_test_statement('test');

        $this->assertEquals('-', $statement->get_setting_last_updated_date('random'));
        $this->assertEquals('-', $statement->get_setting_last_updated_date('notice'));

        $log = new \stdClass();
        $log->userid = 0;
        $log->timemodified = time();
        $log->name = 'notice';
        $log->oldvalue  = 0;
        $log->value = 1;
        $log->plugin = 'integritystmt_test';

        $DB->insert_record('config_log', $log);
        $this->assertEquals(userdate($log->timemodified), $statement->get_setting_last_updated_date('notice'));

        $log->timemodified = time() + 100;
        $DB->insert_record('config_log', $log);
        $this->assertEquals(userdate($log->timemodified), $statement->get_setting_last_updated_date('notice'));
    }

    /**
     * Test checking for changing default value.
     */
    public function test_can_change_default() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $context = \context_course::instance($course->id);

        $this->setUser($user);

        foreach (statement_factory::get_statements() as $statement) {
            $this->assertFalse($statement->can_change_default($context));
        }

        $role = $this->getDataGenerator()->create_role();
        role_assign($role, $user->id, $context);

        foreach (statement_factory::get_statements() as $name => $statement) {
            assign_capability('integritystmt/' . $name . ':changedefault', CAP_ALLOW, $role, $context);
            $this->assertTrue($statement->can_change_default($context));
        }
    }

    /**
     * Test we can check if the statement was agreed by a user.
     */
    public function test_is_agreed_by_user() {
        $this->resetAfterTest();

        $context = \context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $statement = $this->get_test_statement('test');

        $this->assertFalse($statement->is_agreed_by_user($context));
        $this->assertFalse($statement->is_agreed_by_user($context, $user->id));

        $userdata = new userdata_default('integritystmt_test');
        $userdata->add_context_id($context->id, $user->id);

        $statement = $this->get_test_statement('test');
        $this->assertTrue($statement->is_agreed_by_user($context));
        $this->assertTrue($statement->is_agreed_by_user($context, $user->id));
    }

    /**
     * Helper method to set up data for texting should display logic.
     */
    protected function set_up_data_for_should_display() {
        global $PAGE;

        $this->resetAfterTest();
        $context = \context_system::instance();
        $url = new \moodle_url('/test/edit.php', ['id' => 1]);
        $PAGE->set_url($url);
        $PAGE->set_context($context);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $settings = new settings();
        $settings->set('contextid', $context->id);
        $settings->set('plugin', 'integritystmt_test');
        $settings->set('enabled', 1);
        $settings->save();

    }

    /**
     * Test on empty user.
     */
    public function test_should_display_empty_user() {
        global $PAGE, $USER;

        $this->set_up_data_for_should_display();
        $statement = $this->get_test_statement('test');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertTrue($statement->should_display($PAGE));

        unset($USER->id);
        $this->assertFalse($statement->should_display($PAGE));
        $this->assertTrue($statement->should_display($PAGE, $user->id));
    }

    /**
     * Test on page without URL set.
     */
    public function test_should_display_page_without_url() {
        global $PAGE;

        $this->set_up_data_for_should_display();
        $statement = $this->get_test_statement('test');

        $this->assertTrue($statement->should_display($PAGE));

        $page = new \moodle_page();
        $this->assertFalse($statement->should_display($page));
    }

    /**
     * Test if URL doesn't match.
     */
    public function test_should_display_page_not_matching_url() {
        global $PAGE;

        $this->set_up_data_for_should_display();
        $statement = $this->get_test_statement('test');

        $this->assertTrue($statement->should_display($PAGE));

        $PAGE->set_url(new \moodle_url('/test/index.js', ['id' => 1]));
        $this->assertFalse($statement->should_display($PAGE));
    }

    /**
     * Test if the statement is disabled for the context.
     */
    public function test_should_display_disabled_in_context() {
        global $PAGE;

        $this->set_up_data_for_should_display();
        $statement = $this->get_test_statement('test');

        $this->assertTrue($statement->should_display($PAGE));

        $context = \context_system::instance();
        $settings = settings::get_record(['contextid' => $context->id, 'plugin' => $statement->get_plugin_name()]);
        $settings->set('enabled', 0);
        $settings->save();

        $this->assertFalse($statement->should_display($PAGE));
    }

    /**
     * Test when user already agreed.
     */
    public function test_should_display_user_already_agreed() {
        global $PAGE;

        $this->set_up_data_for_should_display();
        $statement = $this->get_test_statement('test');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertTrue($statement->should_display($PAGE));

        $context = \context_system::instance();
        $userdata = new userdata_default($statement->get_plugin_name());
        $userdata->add_context_id($context->id, $user->id);

        $this->assertFalse($statement->should_display($PAGE));
    }

}
