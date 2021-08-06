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
 * Class containing user data.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity;

defined('MOODLE_INTERNAL') || die();

/**
 * Class containing user data.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userdata_default implements userdata_interface {

    /**
     * Table name.
     */
    const TABLE = 'local_integrity_userdata';

    /**
     * @var
     */
    private $id = null;

    /**
     * User id.
     * @var int
     */
    private $userid;

    /**
     * Plugin name.
     * @var string
     */
    private $plugin;

    /**
     * A list of context id for that user.
     * @var array
     */
    private $contextids = [];

    /**
     * Constructor.
     *
     * @param int $userid User ID.
     * @param string $plugin Plugin name.
     *
     * @throws \dml_exception
     */
    public function __construct(int $userid, string $plugin) {
        global $DB;

        $this->userid = $userid;
        $this->plugin = $plugin;

        $record = $DB->get_record(self::TABLE, ['userid' => $this->userid, 'plugin' => $this->plugin]);
        if (!empty($record)) {
            $this->id = $record->id;
            $this->contextids = json_decode($record->contextids);
        }
    }

    /**
     * Return a list of context ids for the user for the plugin,
     * @return array
     */
    public function get_context_ids(): array {
        return $this->contextids;
    }

    /**
     * Add context ID to the list.
     *
     * @param int $contextid
     */
    public function add_context_id(int $contextid): void {
        if (!in_array($contextid, $this->contextids)) {
            $this->contextids[] = $contextid;
            $this->save();
        }
    }

    /**
     * Remove context ID from the list.
     *
     * @param int $contextid
     */
    public function remove_context_id(int $contextid): void {
        if (($key = array_search($contextid, $this->contextids)) !== false) {
            unset($this->contextids[$key]);
            $this->save();
        }
    }

    /**
     * Check if the provided context ID exists in  the list.
     *
     * @param int $contextid
     * @return bool
     */
    public function is_context_id_exist(int $contextid): bool {
        return in_array($contextid, $this->contextids);
    }

    /**
     * Save data to DB.
     */
    protected function save() {
        global $DB;

        $data = new \stdClass();
        $data->userid = $this->userid;
        $data->plugin = $this->plugin;
        $data->contextids = json_encode($this->contextids);

        if (empty($this->id)) {
            $this->id = $DB->insert_record(self::TABLE, $data);
        } else {
            $data->id = $this->id;
            $DB->update_record(self::TABLE, $data);
        }
    }

}
