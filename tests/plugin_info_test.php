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
use local_integrity\plugininfo\integritystmt;
use core_plugin_manager;

/**
 * Tests for sub plugins system.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \local_integrity\plugininfo\integritystmt
 */
class plugin_info_test extends advanced_testcase {

    /**
     * Test a list of enabled plugins.
     */
    public function test_get_enabled_plugins() {
        $expected = [];

        foreach (core_plugin_manager::instance()->get_installed_plugins('integritystmt') as $name => $version) {
            $expected[$name] = $name;
        }

        $this->assertSame($expected, integritystmt::get_enabled_plugins());
    }

}
