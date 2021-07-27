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
 * Plugin manager class.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activity_notifications;

defined('MOODLE_INTERNAL') || die;

/**
 * Plugin manager class.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_manager {

    /**
     * Plugins type.
     */
    const PLUGIN_TYPE = 'activitynotif';

    /**
     * Required file for plugins.
     */
    const CLASS_FILE = 'classes/notification.php';

    /**
     * A singleton instance of this class.
     * @var \local_activity_notifications\plugin_manager
     */
    private static $instance;

    /**
     * A list of enabled plugin.
     * @var \local_activity_notifications\plugininfo\activitynotif[]
     */
    private static $plugins;

    /**
     * Direct initiation not allowed, use the factory method {@see plugin_manager::instance()}
     */
    protected function __construct() {
    }

    /**
     * Sorry, this is singleton
     */
    protected function __clone() {
    }

    /**
     * Factory method for this class .
     *
     * @return \local_activity_notifications\plugin_manager the singleton instance
     */
    public static function instance(): plugin_manager {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Return a list of all enabled plugins.
     *
     * @return \local_activity_notifications\plugininfo\activitynotif[]
     */
    public function get_enabled_plugins(): array {
        if (is_null(static::$plugins)) {
            static::$plugins = $this->build_plugins();
        }

        return static::$plugins;
    }

    /**
     * Build a list of enabled plugins.
     *
     * @return \core\plugininfo\base[]
     */
    protected function build_plugins(): array {
        $plugins = [];

        $pluginswithfile = \core_component::get_plugin_list_with_file(self::PLUGIN_TYPE, self::CLASS_FILE);
        $pluginsinstalled = \core_plugin_manager::instance()->get_plugins_of_type(self::PLUGIN_TYPE);

        foreach ($pluginsinstalled as $name => $plugin) {
            $plugins[$name] = $plugin;

            if (array_key_exists($name, $pluginswithfile)) {
                $plugins[$name] = $plugin;
            }
        }

        return $plugins;
    }

}
