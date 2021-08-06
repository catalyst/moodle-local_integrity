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
 * External functions used by local_integrity plugin.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_integrity\statement_factory;

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * External functions used by local_integrity plugin.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_integrity_external extends \external_api {

    /**
     * Define the parameters for get_statement_notice webservice.
     *
     * @return \external_function_parameters
     */
    public static function get_statement_notice_parameters(): external_function_parameters {
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
    public static function get_statement_notice(string $name): array {
        $result = [];
        $params = self::validate_parameters(self::get_statement_notice_parameters(), ['name' => $name]);

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
    public static function get_statement_notice_returns(): external_single_structure {
        return new external_single_structure([
            'notice' => new external_value(PARAM_RAW, 'The assessment ID.'),
        ]);
    }

}
