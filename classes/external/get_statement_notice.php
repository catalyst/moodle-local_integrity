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

use core\exception\invalid_parameter_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_integrity\statement_factory;

/**
 * Get statement notice WS.
 *
 * @package     local_integrity
 * @copyright   2024 Catalyst IT Australia
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_statement_notice extends external_api {
    /**
     * Define the parameters for get_statement_notice webservice.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_ALPHANUMEXT, 'The name of the statement'),
        ]);
    }

    /**
     * Get statement notice.
     *
     * @param string $name Name of the statement.
     * @return array
     */
    public static function execute(string $name): array {
        $result = [];
        $params = self::validate_parameters(self::execute_parameters(), ['name' => $name]);

        $statement = statement_factory::get_statement($params['name']);

        if (empty($statement)) {
            throw new invalid_parameter_exception('Statement with the provided name is not available. Name: ' . $name);
        }

        $result['notice'] = $statement->get_notice();

        return $result;
    }

    /**
     * Define the get_statement_notice webservice response.
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'notice' => new external_value(PARAM_RAW, 'The assessment ID.'),
        ]);
    }
}
