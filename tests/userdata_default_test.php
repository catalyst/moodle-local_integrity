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

/**
 * Tests for default_userdata class.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \local_integrity\userdata_default
 */
class userdata_default_test extends advanced_testcase {

    /**
     * Set up tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        parent::setUp();
    }

    /**
     * Test empty data.
     */
    public function test_empty_data() {
        $user = $this->getDataGenerator()->create_user();

        $userdata = new userdata_default('test');
        $this->assertCount(0, $userdata->get_context_ids($user->id));
        $this->assertFalse($userdata->is_context_id_exist(rand(), $user->id));
        $userdata->remove_context_id(2, $user->id);
        $this->assertCount(0, $userdata->get_context_ids($user->id));
    }

    /**
     * Test can add and delete.
     */
    public function test_can_add_and_delete() {
        $user = $this->getDataGenerator()->create_user();

        $userdata = new userdata_default('test');
        $this->assertCount(0, $userdata->get_context_ids($user->id));
        $this->assertFalse($userdata->is_context_id_exist($user->id, rand()));

        $userdata->add_context_id(50, $user->id);
        $userdata->add_context_id(51, $user->id);
        $userdata->add_context_id(55, $user->id);

        $this->assertCount(3, $userdata->get_context_ids($user->id));
        $this->assertTrue($userdata->is_context_id_exist(50, $user->id));
        $this->assertTrue($userdata->is_context_id_exist(51, $user->id));
        $this->assertTrue($userdata->is_context_id_exist(55, $user->id));

        $userdata = new userdata_default('test');
        $this->assertCount(3, $userdata->get_context_ids($user->id));
        $this->assertTrue($userdata->is_context_id_exist(50, $user->id));
        $this->assertTrue($userdata->is_context_id_exist(51, $user->id));
        $this->assertTrue($userdata->is_context_id_exist(55, $user->id));

        $userdata->remove_context_id(50, $user->id);
        $this->assertCount(2, $userdata->get_context_ids($user->id));
        $this->assertFalse($userdata->is_context_id_exist(50, $user->id));
        $this->assertTrue($userdata->is_context_id_exist(51, $user->id));
        $this->assertTrue($userdata->is_context_id_exist(55, $user->id));

        $userdata->remove_context_id(51, $user->id);
        $this->assertFalse($userdata->is_context_id_exist(50, $user->id));
        $this->assertFalse($userdata->is_context_id_exist(51, $user->id));
        $this->assertTrue($userdata->is_context_id_exist(55, $user->id));

        $userdata->remove_context_id(55, $user->id);
        $this->assertFalse($userdata->is_context_id_exist(50, $user->id));
        $this->assertFalse($userdata->is_context_id_exist(51, $user->id));
        $this->assertFalse($userdata->is_context_id_exist(55, $user->id));
        $this->assertCount(0, $userdata->get_context_ids($user->id));

        $userdata = new userdata_default('test');
        $this->assertCount(0, $userdata->get_context_ids($user->id));
    }

    /**
     * Test can't add more than once.
     */
    public function test_can_not_add_more_than_one_time() {
        $user = $this->getDataGenerator()->create_user();

        $userdata = new userdata_default('test');
        $this->assertCount(0, $userdata->get_context_ids($user->id));
        $this->assertFalse($userdata->is_context_id_exist($user->id, rand()));

        $userdata->add_context_id(50, $user->id);
        $userdata->add_context_id(50, $user->id);
        $this->assertCount(1, $userdata->get_context_ids($user->id));

        $userdata = new userdata_default('test');
        $this->assertCount(1, $userdata->get_context_ids($user->id));
    }

    /**
     * Test that data gets cached.
     */
    public function test_data_cached() {
        global $DB;

        $cache = \cache::make('local_integrity', 'userdata');
        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse($cache->get('test_' . $user->id));

        $userdata = new userdata_default('test');
        $this->assertCount(0, $userdata->get_context_ids($user->id));
        $this->assertFalse($userdata->is_context_id_exist($user->id, rand()));
        $this->assertNull($cache->get('test_' . $user->id));

        $expected = new \stdClass();
        $expected->userid = $user->id;
        $expected->plugin = 'test';

        $userdata->add_context_id(50, $user->id);
        $records = $DB->get_records(userdata_default::TABLE, ['userid' => $user->id, 'plugin' => 'test']);
        $expected->contextids = [];
        foreach ($records as $record) {
            $expected->contextids[] = $record->contextid;
        }
        $this->assertEquals($expected, $cache->get('test_' . $user->id));

        $userdata->add_context_id(51, $user->id);
        $records = $DB->get_records(userdata_default::TABLE, ['userid' => $user->id, 'plugin' => 'test']);
        $expected->contextids = [];
        foreach ($records as $record) {
            $expected->contextids[] = $record->contextid;
        }
        $this->assertEquals($expected, $cache->get('test_' . $user->id));

        $userdata->remove_context_id(50, $user->id);
        $records = $DB->get_records(userdata_default::TABLE, ['userid' => $user->id, 'plugin' => 'test']);
        $expected->contextids = [];
        foreach ($records as $record) {
            $expected->contextids[] = $record->contextid;
        }
        $this->assertEquals($expected, $cache->get('test_' . $user->id));

        $userdata->remove_context_id(50, $user->id);
        $records = $DB->get_records(userdata_default::TABLE, ['userid' => $user->id, 'plugin' => 'test']);
        $expected->contextids = [];
        foreach ($records as $record) {
            $expected->contextids[] = $record->contextid;
        }
        $this->assertEquals($expected, $cache->get('test_' . $user->id));

        $userdata->remove_context_id(51, $user->id);
        $this->assertEquals(null, $cache->get('test_' . $user->id));
    }

}
