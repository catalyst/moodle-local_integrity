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
use local_integrity\statement_base;
use local_integrity\statement_factory;

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
    public function get_plugin_name() {
        $statement = $this->get_test_statement('test');
        $this->assertSame('integritystmt_test', $statement->get_name());
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
     * Test should display.
     */
    public function test_should_display() {
        $statement = $this->get_test_statement('test');

        $url = new \moodle_url('/test/edit/index.php', ['id' => 1]);
        $this->assertFalse($statement->should_display_for_url($url));

        $url = new \moodle_url('/test/index.js', ['id' => 1]);
        $this->assertFalse($statement->should_display_for_url($url));

        $url = new \moodle_url('/test/index.php', ['id' => 1]);
        $this->assertTrue($statement->should_display_for_url($url));

        $url = new \moodle_url('/test/edit.php', ['id' => 1]);
        $this->assertTrue($statement->should_display_for_url($url));
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

}
