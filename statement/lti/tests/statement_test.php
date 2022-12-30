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
 * Tests for statement class.
 *
 * @package     integritystmt_lti
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace integritystmt_lti\tests;

use advanced_testcase;
use integritystmt_lti\statement;

/**
 * Tests for statement class.
 *
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group local_integrity
 */
class statement_test extends advanced_testcase {

    /**
     * Test a list of urls to display the statement at.
     */
    public function test_get_display_urls() {
        $statement = new statement('lti');
        $expected = [
            '/mod/lti/index.php',
            '/mod/lti/view.php',
            '/local/integrity/statement/lti/launch.php',
        ];

        $this->assertSame($expected, $statement->get_display_urls());
    }

    /**
     * Test getting agree url without set id parameter.
     */
    public function test_get_agree_url_without_id() {
        $statement = new statement('lti');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('A required parameter (id) was missing');

        $this->assertSame('', $statement->get_agree_url());
    }

    /**
     * Test getting agree url without set page having url set.
     */
    public function test_get_agree_url_without_page_having_url_set() {
        $_GET['id'] = 1;
        $statement = new statement('lti');

        $this->assertSame('', $statement->get_agree_url());
    }

    /**
     * Test getting agree url with set page having incorrect url set.
     */
    public function test_get_agree_url_with_page_url_not_matching_launch_url() {
        global $PAGE;

        $_GET['id'] = 1;
        $PAGE->set_url('/login/index.php');
        $statement = new statement('lti');

        $this->assertSame('', $statement->get_agree_url());
    }

    /**
     * Test getting agree url with triggerview set to 1.
     */
    public function test_get_agree_url_with_triggerview_set_to_one() {
        global $PAGE;

        $_GET['id'] = 11;
        $_GET['triggerview'] = 1;

        $PAGE->set_url('/local/integrity/statement/lti/launch.php');
        $statement = new statement('lti');

        $this->assertSame('https://www.example.com/moodle/mod/lti/launch.php?id=11&triggerview=1', $statement->get_agree_url());
    }

    /**
     * Test getting agree url with triggerview set to 0.
     */
    public function test_get_agree_url_with_triggerview_set_to_nil() {
        global $PAGE;

        $_GET['id'] = 55;
        $_GET['triggerview'] = 0;

        $PAGE->set_url('/local/integrity/statement/lti/launch.php');
        $statement = new statement('lti');

        $this->assertSame('https://www.example.com/moodle/mod/lti/launch.php?id=55&triggerview=0', $statement->get_agree_url());
    }

    /**
     * Test getting agree url without triggerview set.
     */
    public function test_get_agree_url_without_triggerview_set() {
        global $PAGE;

        $_GET['id'] = 777;

        $PAGE->set_url('/local/integrity/statement/lti/launch.php');
        $statement = new statement('lti');

        $this->assertSame('https://www.example.com/moodle/mod/lti/launch.php?id=777&triggerview=1', $statement->get_agree_url());
    }

}
