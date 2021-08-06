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

namespace local_integrity\tests;

use advanced_testcase;
use external_api;
use local_integrity\statement_factory;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../externallib.php');

/**
 * Tests for external lib functions.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class externallib_test extends advanced_testcase {

    /**
     * Test requesting statement's notice with incorrect name.
     */
    public function test_requesting_notice_for_incorrect_statement_name() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $_POST['sesskey'] = sesskey();

        $params = ['name' => 'test'];
        $response = external_api::call_external_function('local_integrity_get_statement_notice', $params, true);
        $this->assertTrue($response['error']);
        $this->assertSame('invalidparameter', $response['exception']->errorcode);
        $this->assertStringContainsString(
            'Statement with the provided name is not available. Name: test',
            $response['exception']->debuginfo
        );
    }

    /**
     * Test requesting statement's notice with incorrect name.
     */
    public function test_requesting_notice_for_correct_statements() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $_POST['sesskey'] = sesskey();

        foreach (statement_factory::get_statements() as $name => $statement) {
            $params = ['name' => $name];

            // Test empty config first.
            $response = external_api::call_external_function('local_integrity_get_statement_notice', $params, true);
            $this->assertFalse($response['error']);
            $expected = '';
            $this->assertSame($expected, $response['data']['notice']);

            // Set notice and test that we get it back.
            $expected = 'New notice value for ' . $name;
            set_config('notice', $expected, $statement->get_plugin_name());

            $response = external_api::call_external_function('local_integrity_get_statement_notice', $params, true);
            $this->assertFalse($response['error']);
            $this->assertSame($expected, $response['data']['notice']);
        }
    }

}
