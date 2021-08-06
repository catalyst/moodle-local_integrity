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
 * Interface described user data.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface described user data.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface userdata_interface {

    /**
     * Return a list of context ids for the user for the plugin,
     * @return array
     */
    public function get_context_ids(): array;

    /**
     * Add context ID to the list.
     *
     * @param int $contextid
     */
    public function add_context_id(int $contextid): void;

    /**
     * Remove context ID from the list.
     *
     * @param int $contextid
     */
    public function remove_context_id(int $contextid): void;

    /**
     * Check if the provided context ID exists in  the list.
     *
     * @param int $contextid
     * @return bool
     */
    public function is_context_id_exist(int $contextid): bool;

}
