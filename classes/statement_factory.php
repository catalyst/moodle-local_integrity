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
 * Statement factory.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity;

use local_integrity\plugininfo\integritystmt;

defined('MOODLE_INTERNAL') || die();

/**
 * Statement factory.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class statement_factory {

    /**
     * Get list of all statement instances.
     *
     * @return statement_base[]
     */
    public static function get_statements(): array {
        $statements = [];

        foreach (integritystmt::get_enabled_plugins() as $name) {
            $statements[$name] = self::build_statement($name);
        }

        return $statements;
    }

    /**
     * Get a statement instance of the given name.
     *
     * @param string $name Name of the statement.
     *
     * @return null|\local_integrity\statement_base
     */
    public static function get_statement(string $name): ?statement_base {
        $statement = null;
        $statements = self::get_statements();
        if (!empty($statements[$name])) {
            $statement = $statements[$name];
        }

        return $statement;
    }

    /**
     * Build a statement instance for the given name.
     *
     * @param string $name Name of the statement.
     *
     * @return \local_integrity\statement_base
     */
    protected static function build_statement(string $name): statement_base {
        $class = '\\integritystmt_' . $name . '\\statement';

        if (!class_exists($class)) {
            throw new \coding_exception('Invalid statement plugin integritystmt_' . $name);
        }

        return new $class($name);
    }

}
