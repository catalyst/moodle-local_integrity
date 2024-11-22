<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_integrity\external;

use context_system;
use core\exception\invalid_parameter_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_integrity\statement_factory;

/**
 * Ws to agree statement.
 *
 * @package     local_integrity
 * @copyright   2024 Catalyst IT Australia
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agree_statement extends external_api {

    /**
     * Define the parameters for agree_statement webservice.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_ALPHANUMEXT, 'The name of the statement.'),
            'contextid' => new external_value(PARAM_INT, 'Context ID the statement needs to be agreed in.'),
            'userid' => new external_value(PARAM_INT, 'Optional user ID. Otherwise the current user us used.'),

        ]);
    }

    /**
     * Agree provided statement in the provided context.
     *
     * @param string $name Name of the statement.
     * @param int $contextid Context ID to agree.
     * @param int $userid Optional user ID.
     *
     * @return array
     */
    public static function execute(string $name, int $contextid, int $userid = 0): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'contextid' => $contextid,
            'userid' => $userid
        ]);

        $statement = statement_factory::get_statement($params['name']);

        if (empty($statement)) {
            throw new invalid_parameter_exception('Statement with the provided name is not available. Name: ' . $name);
        }

        if (empty($userid)) {
            $userid = $USER->id;
        } else if ($userid != $USER->id) {
            require_capability('local/integrity:agreestatements', context_system::instance());
        }

        $statement->get_user_data()->add_context_id($contextid, $userid);

        return [];
    }

    /**
     * Define the agree_statement webservice response object shape.
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
