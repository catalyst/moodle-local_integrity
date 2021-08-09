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
     * Plugin name.
     * @var string
     */
    private $plugin;

    /**
     * Constructor.
     *
     * @param string $plugin Plugin name.
     */
    public function __construct(string $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Return a list of context ids for the user for the plugin.
     * @param int $userid User ID.
     *
     * @return array
     */
    public function get_context_ids(int $userid): array {
        $contextids = [];
        $userdata = $this->get_user_data($userid);

        if (!empty($userdata)) {
            $contextids = $userdata->contextids;
        }

        return $contextids;
    }

    /**
     * Add context ID to the list.
     *
     * @param int $contextid Context ID.
     * @param int $userid User ID.
     */
    public function add_context_id(int $contextid, int $userid): void {
        $userdata = $this->get_user_data($userid);

        if (!empty($userdata)) {
            if (!in_array($contextid, $userdata->contextids)) {
                $userdata->contextids[] = $contextid;
                $this->save_user_data($userdata);
            }
        } else {
            $userdata = new \stdClass();
            $userdata->userid = $userid;
            $userdata->plugin = $this->plugin;
            $userdata->contextids = [$contextid];
            $this->save_user_data($userdata);
        }
    }

    /**
     * Remove context ID from the list.
     *
     * @param int $contextid Context ID.
     * @param int $userid User ID.
     */
    public function remove_context_id(int $contextid, int $userid): void {
        $userdata = $this->get_user_data($userid);

        if (!empty($userdata)) {
            if (($key = array_search($contextid, $userdata->contextids)) !== false) {
                unset($userdata->contextids[$key]);
                $userdata->contextids = array_values($userdata->contextids);
                $this->save_user_data($userdata);
            }
        }
    }

    /**
     * Check if the provided context ID exists in  the list.
     *
     * @param int $contextid Context ID.
     * @param int $userid User ID.
     *
     * @return bool
     */
    public function is_context_id_exist(int $contextid, int $userid): bool {
        $result = false;
        $userdata = $this->get_user_data($userid);

        if ($userdata) {
            $result = in_array($contextid, $userdata->contextids);
        }

        return $result;
    }

    /**
     * Get user data for provided user.
     *
     * @param int $userid User ID.
     * @return \stdClass|null
     */
    private function get_user_data(int $userid): ?\stdClass {
        global $DB;

        if ($userdata = $DB->get_record(self::TABLE, ['userid' => $userid, 'plugin' => $this->plugin])) {
            $userdata->contextids = json_decode($userdata->contextids);

            return $userdata;
        }

        return null;
    }

    /**
     * Save provided user data.
     *
     * @param \stdClass $userdata User data object.
     */
    private function save_user_data(\stdClass $userdata) {
        global $DB;

        $userdata->contextids = json_encode($userdata->contextids);

        if (!empty($userdata->id)) {
            $DB->update_record(self::TABLE, $userdata);
        } else {
            $DB->insert_record(self::TABLE, $userdata);
        }
    }

}
