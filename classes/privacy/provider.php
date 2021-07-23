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
 * Privacy Subsystem implementation for local_activity_notifications.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activity_notifications\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_activity_notifications\activity_notifications;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for local_activity_notifications.
 *
 * @package     local_activity_notifications
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Retrieve the user metadata stored by plugin.
     *
     * @param collection $collection Collection of metadata.
     * @return collection Collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_activity_notifications',
            [
                'cmid' => 'privacy:metadata:local_activity_notifications:cmid',
                'usermodified' => 'privacy:metadata:local_activity_notifications:usermodified',
                'timecreated' => 'privacy:metadata:local_activity_notifications:timecreated',
                'timemodified' => 'privacy:metadata:local_activity_notifications:timemodified',
            ],
            'privacy:metadata:local_activity_notifications'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist A list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ];

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {local_activity_notifications} lan ON lan.cmid = cm.id
                 WHERE lan.usermodified = :userid
        ";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $cmids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_MODULE) {
                $cmids[] = $context->instanceid;
            }
        }

        if (empty($cmids)) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $params['usermodified'] = $contextlist->get_user()->id;

        $sql = "SELECT *
                  FROM {local_activity_notifications}
                 WHERE usermodified = :usermodified AND cmid " . $insql;

        $activitynotifications = $DB->get_records_sql($sql, $params);

        $index = 0;
        foreach ($activitynotifications as $activitynotification) {
            // Data export is organised in: {Context}/{Plugin Name}/{Table name}/{index}/data.json.
            $index++;
            $subcontext = [
                get_string('pluginname', 'local_activity_notifications'),
                activity_notifications::TABLE,
                $index
            ];

            $data = (object) [
                'cmid' => $activitynotification->cmid,
                'enabled' => $activitynotification->enabled,
                'usermodified' => $activitynotification->usermodified,
                'timecreated' => transform::datetime($activitynotification->timecreated),
                'timemodified' => transform::datetime($activitynotification->timemodified)
            ];

            $context = \context_module::instance($activitynotification->cmid);
            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $cmid = $context->instanceid;

        list($insql, $params) = $DB->get_in_or_equal($cmid, SQL_PARAMS_NAMED);

        // We don't want to delete records. Just anonymise the users.
        $DB->set_field_select('local_activity_notifications', 'usermodified', 0, "cmid $insql", $params);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $contexts = $contextlist->get_contexts();
        if (count($contexts) == 0) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_MODULE) {
                $cmids[] = $context->instanceid;
            }
        }

        if (empty($cmids)) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $params['usermodified'] = $contextlist->get_user()->id;

        // We don't want to delete records. Just anonymise the users.
        $DB->set_field_select('local_activity_notifications', 'usermodified', 0, "cmid $insql", $params);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT usermodified AS userid
                  FROM {local_activity_notifications}
                 WHERE cmid = :cmid";

        $params = [
            'cmid' => $context->instanceid
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // We don't want to delete records. Just anonymise the users.
        $DB->set_field_select('local_activity_notifications', 'usermodified', 0, "usermodified {$insql}", $inparams);
    }
}
