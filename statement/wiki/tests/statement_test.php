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
 * @package     integritystmt_wiki
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace integritystmt_wiki\tests;

use advanced_testcase;
use integritystmt_wiki\statement;

defined('MOODLE_INTERNAL') || die();

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
        $statement = new statement('wiki');
        $expected = [
            '/mod/wiki/index.php',
            '/mod/wiki/view.php',
            '/mod/wiki/create.php',
            '/mod/wiki/edit.php',
            '/mod/wiki/comments.php',
            '/mod/wiki/history.php',
            '/mod/wiki/map.php',
            '/mod/wiki/files.php',
        ];

        $this->assertSame($expected, $statement->get_display_urls());
    }

}
