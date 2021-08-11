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
     * Cache instance.
     * @var \cache
     */
    private $cache;

    /**
     * Constructor.
     *
     * @param string $plugin Plugin name.
     */
    public function __construct(string $plugin) {
        $this->plugin = $plugin;
        $this->cache = \cache::make('local_integrity', 'userdata');
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
        global $DB;

        $userdata = $this->get_user_data($userid);

        if (empty($userdata) || !in_array($contextid, $userdata->contextids)) {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->plugin = $this->plugin;
            $record->contextid = $contextid;

            $DB->insert_record(self::TABLE, $record);
            $this->refresh_cache($userid);
        }
    }

    /**
     * Remove context ID from the list.
     *
     * @param int $contextid Context ID.
     * @param int $userid User ID.
     */
    public function remove_context_id(int $contextid, int $userid): void {
        global $DB;

        $userdata = $this->get_user_data($userid);

        if (!empty($userdata) && in_array($contextid, $userdata->contextids)) {
            $DB->delete_records(self::TABLE, ['userid' => $userid, 'plugin' => $this->plugin, 'contextid' => $contextid]);
            $this->refresh_cache($userid);
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

        if (!empty($userdata)) {
            $result = in_array($contextid, $userdata->contextids);
        }

        return $result;
    }

    /**
     * Build cache key based on provided user.
     *
     * @param int $userid User ID.
     * @return string
     */
    private function build_cache_key(int $userid): string {
        return $this->plugin . '_' . $userid;
    }

    /**
     * Get user data for provided user.
     *
     * @param int $userid User ID.
     * @return \stdClass|null
     */
    private function get_user_data(int $userid): ?\stdClass {
        global $DB;

        $userdata = $this->cache->get($this->build_cache_key($userid));

        if ($userdata === false) {
            $records = $DB->get_records(self::TABLE, ['userid' => $userid, 'plugin' => $this->plugin]);

            if (empty($records)) {
                $userdata = null;
            } else {
                $userdata = new \stdClass();
                $userdata->userid = $userid;
                $userdata->plugin = $this->plugin;
                $userdata->contextids = [];
                foreach ($records as $record) {
                    $userdata->contextids[] = $record->contextid;
                }
            }

            $this->cache->set($this->build_cache_key($userid), $userdata);
        }

        return $userdata;
    }

    /**
     * Refresh cache for the user.
     *
     * @param int $userid User ID.
     */
    private function refresh_cache(int $userid) {
        $this->cache->delete($this->build_cache_key($userid));
        $this->cache->set($this->build_cache_key($userid), $this->get_user_data($userid));
    }

}
