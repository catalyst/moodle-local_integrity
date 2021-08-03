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
 * Sub plugin class.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activity_notifications\plugininfo;

use core\plugininfo\base;
use core_plugin_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Sub plugin class.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitynotif extends base {

    /**
     * Gets all enabled plugins.
     *
     * @return string[]
     */
    public static function get_enabled_plugins(): array {
        return array_keys(core_plugin_manager::instance()->get_installed_plugins('activitynotif'));
    }

}
