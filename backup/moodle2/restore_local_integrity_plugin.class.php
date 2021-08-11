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
 * Restore implementation.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \local_integrity\settings;

/**
 * Restore implementation.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_integrity_plugin extends restore_local_plugin {

    /**
     * Returns the paths to be handled by the plugin at activity level.
     */
    protected function define_module_plugin_structure() {
        $paths = [];

        $elename = 'setting';
        $elepath = $this->get_pathfor('/settings/setting');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Process the setting element.
     *
     * @param array $data
     */
    public function process_setting(array $data) {
        global $DB;

        $data = (object)$data;
        unset($data->id);
        $data->contextid = $this->task->get_contextid();
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $DB->insert_record(settings::TABLE, $data);
    }

}
