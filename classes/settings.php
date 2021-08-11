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
 * Class containing settings for activities.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity;

use core\persistent;

defined('MOODLE_INTERNAL') || die();

/**
 * Class containing settings for activities.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings extends persistent {

    /**
     * Table name.
     */
    const TABLE = 'local_integrity_settings';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'contextid' => [
                'type' => PARAM_INT,
            ],
            'plugin' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'enabled' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * Get cache instance.
     *
     * @return \cache
     */
    private static function get_cache(): \cache {
        return \cache::make('local_integrity', 'settings');
    }

    /**
     * Build cache key from provided plugin name and context id.
     *
     * @param string $pluginname Name of the plugin.
     * @param int $contextid Context ID.
     *
     * @return string
     */
    private static function build_cache_key(string $pluginname, int $contextid): string {
        return $pluginname . '_' . $contextid;
    }

    /**
     * Get cache key.
     *
     * @return string
     */
    private function get_cache_key(): string {
        return self::build_cache_key($this->get('plugin'), $this->get('contextid'));
    }

    /**
     * Run after update.
     *
     * @param bool $result Result of update.
     */
    protected function after_update($result) {
        if ($result) {
            self::get_cache()->set($this->get_cache_key(), $this->to_record());
        }
    }

    /**
     * Run after created.
     */
    protected function after_create() {
        self::get_cache()->set($this->get_cache_key(), $this->to_record());
    }

    /**
     * Run after deleted.
     *
     * @param bool $result Result of delete.
     */
    protected function after_delete($result) {
        if ($result) {
            self::get_cache()->delete($this->get_cache_key());
        }
    }

    /**
     * Get settings object.
     *
     * @param string $pluginname Name of the plugin.
     * @param int $contextid Context ID.
     *
     * @return \local_integrity\settings|null
     */
    public static function get_settings(string $pluginname, int $contextid): ?settings {
        $cachekey = self::build_cache_key($pluginname, $contextid);
        $settings = self::get_cache()->get($cachekey);

        if ($settings !== false) {
            if (!is_null($settings)) {
                return new static(0, $settings);
            } else {
                return null;
            }
        }

        $settings = self::get_record(['plugin' => $pluginname, 'contextid' => $contextid]);

        if (!empty($settings)) {
            self::get_cache()->set($cachekey, $settings->to_record());
        } else {
            $settings = null;
            self::get_cache()->set($cachekey, $settings);
        }

        return $settings;
    }

}
