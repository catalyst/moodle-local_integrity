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
 * Tests for external lib functions.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity\external;

use advanced_testcase;
use core_external\external_api;
use local_integrity\statement_factory;


/**
 * Tests for external lib functions.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agree_statement_test extends advanced_testcase {

    /**
     * Set up tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        parent::setUp();
    }

    /**
     * Test agreeing statement with incorrect name.
     */
    public function test_agree_statement_for_incorrect_statement_name() {
        $context = \context_system::instance();

        $this->setAdminUser();

        $_POST['sesskey'] = sesskey();

        $params = ['name' => 'test', 'contextid' => $context->id, 'userid' => 0];
        $response = external_api::call_external_function('local_integrity_agree_statement', $params, true);
        $this->assertTrue($response['error']);
        $this->assertSame('invalidparameter', $response['exception']->errorcode);
        $this->assertStringContainsString(
            'Statement with the provided name is not available. Name: test',
            $response['exception']->debuginfo
        );
    }

    /**
     * Test agreeing statement with correct name.
     */
    public function test_agree_statement_for_correct_statement_name() {
        global $USER;

        $context = \context_system::instance();

        $this->setAdminUser();

        $_POST['sesskey'] = sesskey();

        foreach (statement_factory::get_statements() as $name => $statement) {
            $params = ['name' => $name, 'contextid' => $context->id, 'userid' => 0];
            $this->assertFalse($statement->is_agreed_by_user($context, $USER->id));
            $response = external_api::call_external_function('local_integrity_agree_statement', $params, true);
            $this->assertFalse($response['error']);
            $this->assertTrue($statement->is_agreed_by_user($context, $USER->id));
        }
    }

    /**
     * Test agreeing statement on behalf of others.
     */
    public function test_agree_statement_on_behalf_of_others() {
        $context = \context_system::instance();

        $wsuser = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();

        $this->setUser($wsuser);

        $_POST['sesskey'] = sesskey();

        // Testing without permissions.
        foreach (statement_factory::get_statements() as $name => $statement) {
            // Update for yourself first.
            $params = ['name' => $name, 'contextid' => $context->id, 'userid' => $wsuser->id];
            $this->assertFalse($statement->is_agreed_by_user($context, $wsuser->id));
            $response = external_api::call_external_function('local_integrity_agree_statement', $params, true);
            $this->assertFalse($response['error']);
            $this->assertTrue($statement->is_agreed_by_user($context, $wsuser->id));

            // Try to update the other user.
            $params = ['name' => $name, 'contextid' => $context->id, 'userid' => $student->id];
            $this->assertFalse($statement->is_agreed_by_user($context, $student->id));
            $response = external_api::call_external_function('local_integrity_agree_statement', $params, true);
            $this->assertTrue($response['error']);
            $this->assertSame('nopermissions', $response['exception']->errorcode);
            $this->assertStringContainsString('Error code: nopermissions', $response['exception']->debuginfo);
            $this->assertFalse($statement->is_agreed_by_user($context, $student->id));
        }

        // Assign required capabilities.
        $role = $this->getDataGenerator()->create_role();
        role_assign($role, $wsuser->id, $context);
        assign_capability('local/integrity:agreestatements', CAP_ALLOW, $role, $context);

        // Testing with permissions.
        foreach (statement_factory::get_statements() as $name => $statement) {
            // Try to update the other user.
            $params = ['name' => $name, 'contextid' => $context->id, 'userid' => $student->id];
            $this->assertFalse($statement->is_agreed_by_user($context, $student->id));
            $response = external_api::call_external_function('local_integrity_agree_statement', $params, true);
            $this->assertFalse($response['error']);
            $this->assertTrue($statement->is_agreed_by_user($context, $student->id));
        }
    }
}
