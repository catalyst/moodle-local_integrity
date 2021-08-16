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
 * Tests for sub plugins system.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity\tests;

use advanced_testcase;
use local_integrity\plugininfo\integritystmt;
use core_plugin_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for sub plugins system.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_info_test extends advanced_testcase {

    /**
     * Set up tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        parent::setUp();
    }

    /**
     * Test a list of enabled plugins.
     */
    public function test_get_enabled_plugins() {
        $expected = ['data', 'forum', 'glossary', 'h5pactivity', 'hsuforum', 'hvp', 'lesson', 'lti', 'quiz', 'scorm', 'workshop'];
        $this->assertSame($expected, integritystmt::get_enabled_plugins());
    }

    /**
     * Test that a list of enabled plugins is cached.
     */
    public function test_get_enabled_plugins_cached() {
        global $CFG;

        $expected = array_keys(core_plugin_manager::instance()->get_installed_plugins('integritystmt'));
        $this->assertSame($expected, integritystmt::get_enabled_plugins());

        $this->assertTrue(!empty($CFG->local_integrity_hash));
        $this->assertTrue(!empty($CFG->integritystmt_plugins));

        $this->assertSame($CFG->allversionshash,  $CFG->local_integrity_hash);
        $this->assertSame(
            json_encode(array_keys(core_plugin_manager::instance()->get_installed_plugins('integritystmt'))),
            $CFG->integritystmt_plugins
        );
    }

}
