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
 * Tests for settings class.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity\tests;

use advanced_testcase;
use local_integrity\settings;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for settings class.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_test extends advanced_testcase {

    /**
     * Test get settings functionality.
     */
    public function test_get_settings() {
        $this->resetAfterTest();

        $contextid = 1;
        $plugin = 'test';

        $this->assertNull(settings::get_settings($plugin, $contextid));
        $this->assertNull(settings::get_settings($plugin, $contextid));

        $settings = new settings();
        $settings->set('contextid', $contextid);
        $settings->set('plugin', $plugin);
        $settings->set('enabled', 0);
        $settings->create();

        $actual = settings::get_settings($plugin, $contextid);
        $this->assertSame($settings->get('contextid'), $actual->get('contextid'));
        $this->assertSame($settings->get('plugin'), $actual->get('plugin'));
        $this->assertSame($settings->get('enabled'), $actual->get('enabled'));

        $settings->set('enabled', 1);
        $settings->save();

        $actual = settings::get_settings($plugin, $contextid);
        $this->assertSame($settings->get('contextid'), $actual->get('contextid'));
        $this->assertSame($settings->get('plugin'), $actual->get('plugin'));
        $this->assertSame($settings->get('enabled'), $actual->get('enabled'));
    }

    /**
     * Test that data gets cached.
     */
    public function test_data_cached() {
        global $DB;

        $this->resetAfterTest();
        $cache = \cache::make('local_integrity', 'settings');
        $contextid = 1;
        $plugin = 'test';
        $cachekey = $plugin . '_' . $contextid;

        $this->assertFalse($cache->get($cachekey));

        $settings = new settings();
        $settings->set('contextid', $contextid);
        $settings->set('plugin', $plugin);
        $settings->set('enabled', 0);
        $settings->create();

        $expected = $DB->get_record(settings::TABLE, ['contextid' => $contextid, 'plugin' => $plugin]);
        $this->assertEquals($expected, $cache->get($cachekey));

        $settings->set('enabled', 1);
        $settings->update();

        $expected = $DB->get_record(settings::TABLE, ['contextid' => $contextid, 'plugin' => $plugin]);
        $this->assertEquals($expected, $cache->get($cachekey));

        $settings->delete();
        $this->assertFalse($cache->get($cachekey));
    }

}
