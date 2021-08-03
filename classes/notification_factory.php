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
 * Notification factory.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activity_notifications;

use local_activity_notifications\plugininfo\activitynotif;

defined('MOODLE_INTERNAL') || die();

/**
 * Notification factory.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_factory {

    /**
     * Get list of all notification instances.
     *
     * @return notification_base[]
     */
    public static function get_notifications(): array {
        $notifications = [];

        foreach (activitynotif::get_enabled_plugins() as $name) {
            $notifications[$name] = self::build_notification($name);
        }

        return $notifications;
    }

    /**
     * Get notification of the given name.
     *
     * @param string $name
     * @return null|\local_activity_notifications\notification_base
     */
    public static function get_notification(string $name): ?notification_base {
        $notification = null;
        $notifications = self::get_notifications();
        if (!empty($notifications[$name])) {
            $notification = $notifications[$name];
        }

        return $notification;
    }

    /**
     * Get notification instance for the given name.
     *
     * @param string $name Name of the notification.
     *
     * @return \local_activity_notifications\notification_base
     */
    protected static function build_notification(string $name): notification_base {
        $class = '\\activitynotif_' . $name . '\\notification';

        if (!class_exists($class)) {
            throw new \coding_exception('Invalid activity notification plugin activitynotif_' . $name);
        }

        return new $class($name);
    }

}
