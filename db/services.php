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
 * Web services used by local_integrity plugin.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_integrity_get_statement_notice' => [
        'classname' => 'local_integrity_external',
        'methodname' => 'get_statement_notice',
        'classpath' => 'local/integrity/externallib.php',
        'description' => 'Get academic integrity notice text',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_integrity_agree_statement' => [
        'classname' => 'local_integrity_external',
        'methodname' => 'agree_statement',
        'classpath' => 'local/integrity/externallib.php',
        'description' => 'Agree integrity statement',
        'type' => 'write',
        'ajax' => true,
    ],
];
