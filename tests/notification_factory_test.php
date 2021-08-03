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
 * Tests for notification factory.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activity_notifications\tests;

use advanced_testcase;
use local_activity_notifications\notification_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for notification factory.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_factory_test extends advanced_testcase {

    /**
     * A list of known notifications.
     * @var string[]
     */
    private $knownnotifications = [
        'forum',
    ];

    /**
     * Test get notifications.
     */
    public function test_get_notifications() {
        $actual = notification_factory::get_notifications();

        $this->assertCount(1, notification_factory::get_notifications());

        foreach ($this->knownnotifications as $name) {
            $this->assertArrayHasKey($name, $actual);
            $this->assertInstanceOf('\\activitynotif_' . $name . '\\notification', $actual[$name]);
        }
    }

    /**
     * Test getting invalid notification.
     */
    public function test_get_invalid_notification() {
        $this->assertNull(notification_factory::get_notification('invalid'));
    }

    /**
     * Test getting valid notification.
     */
    public function test_getting_valid_notification() {
        foreach ($this->knownnotifications as $name) {
            $actual = notification_factory::get_notification($name);
            $this->assertInstanceOf('\\activitynotif_' . $name . '\\notification', $actual);
        }
    }

}
