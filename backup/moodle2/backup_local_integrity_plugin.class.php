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
 * Backup implementation.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_integrity\settings;

defined('MOODLE_INTERNAL') || die;

/**
 * Backup implementation.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_integrity_plugin extends backup_local_plugin {

    /**
     * Returns the information to be attached to a module instance
     */
    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        $plugin->add_child($pluginwrapper);

        $settings = new backup_nested_element('settings');
        $pluginwrapper->add_child($settings);

        $setting = new backup_nested_element(
            'setting',
            ['id'],
            ['contextid', 'plugin', 'enabled', 'usermodified', 'timecreated', 'timemodified']
        );

        $settings->add_child($setting);
        $setting->set_source_table(settings::TABLE, ['contextid' => backup::VAR_CONTEXTID]);
        $setting->annotate_ids('user', 'usermodified');

        return $plugin;
    }

}
