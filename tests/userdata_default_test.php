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
 * Tests for default_userdata class.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity\tests;

use advanced_testcase;
use local_integrity\userdata_default;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for default_userdata class.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userdata_default_test extends advanced_testcase {

    /**
     * Test empty data.
     */
    public function test_empty_data() {
        $userdata = new userdata_default(1, 'test');
        $this->assertCount(0, $userdata->get_context_ids());
        $this->assertFalse($userdata->is_context_id_exist(rand()));
        $userdata->remove_context_id(2);
        $this->assertCount(0, $userdata->get_context_ids());
    }

    /**
     * Test can add and delete.
     */
    public function test_can_add_and_delete() {
        $this->resetAfterTest();

        $userdata = new userdata_default(1, 'test');
        $this->assertCount(0, $userdata->get_context_ids());
        $this->assertFalse($userdata->is_context_id_exist(rand()));

        $userdata->add_context_id(50);
        $userdata->add_context_id(51);
        $userdata->add_context_id(55);

        $this->assertCount(3, $userdata->get_context_ids());
        $this->assertTrue($userdata->is_context_id_exist(50));
        $this->assertTrue($userdata->is_context_id_exist(51));
        $this->assertTrue($userdata->is_context_id_exist(55));

        $userdata = new userdata_default(1, 'test');
        $this->assertCount(3, $userdata->get_context_ids());
        $this->assertTrue($userdata->is_context_id_exist(50));
        $this->assertTrue($userdata->is_context_id_exist(51));
        $this->assertTrue($userdata->is_context_id_exist(55));

        $userdata->remove_context_id(50);
        $this->assertCount(2, $userdata->get_context_ids());
        $this->assertFalse($userdata->is_context_id_exist(50));
        $this->assertTrue($userdata->is_context_id_exist(51));
        $this->assertTrue($userdata->is_context_id_exist(55));

        $userdata->remove_context_id(51);
        $this->assertFalse($userdata->is_context_id_exist(50));
        $this->assertFalse($userdata->is_context_id_exist(51));
        $this->assertTrue($userdata->is_context_id_exist(55));

        $userdata->remove_context_id(55);
        $this->assertFalse($userdata->is_context_id_exist(50));
        $this->assertFalse($userdata->is_context_id_exist(51));
        $this->assertFalse($userdata->is_context_id_exist(55));
        $this->assertCount(0, $userdata->get_context_ids());

        $userdata = new userdata_default(1, 'test');
        $this->assertCount(0, $userdata->get_context_ids());
    }

    /**
     * Test can't add more than once.
     */
    public function test_can_not_add_more_than_one_time() {
        $this->resetAfterTest();

        $userdata = new userdata_default(1, 'test');
        $this->assertCount(0, $userdata->get_context_ids());
        $this->assertFalse($userdata->is_context_id_exist(rand()));

        $userdata->add_context_id(50);
        $userdata->add_context_id(50);
        $this->assertCount(1, $userdata->get_context_ids());

        $userdata = new userdata_default(1, 'test');
        $this->assertCount(1, $userdata->get_context_ids());
    }

}
