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
 * CLI script for default settings value for activity.
 *
 * @package    local_integrity
 * @copyright  2025 Catalyst IT
 * @author     Guillaume BARAT (guillaumebarat@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_integrity\plugininfo\integritystmt;
use local_integrity\statement_factory;
use local_integrity\settings;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'default' => null,
        'help' => false,
    ],
    [
        'd' => 'default',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// Display help in case if requested for a help text or if unexpected default value is provided.
if ($options['help'] || (!isset($options['default']) || !in_array($options['default'], ['enable', 'disable'], true))) {
    $help = <<<EOT
Default value for academic integrity.

Options:
 -d, --default             Set default integrity settings for each activity that has not been set yet.
 -h, --help                Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/integrity/cli/default.php --default=enable,disable

EOT;
    cli_writeln($help);
    exit(0);
}

if (isset($options['default']) && in_array($options['default'], ['enable', 'disable'], true)) {
    $integritytable = settings::TABLE;
    switch ($options['default']) {
        case 'enable':
            $enabled = 1;
            break;
        case 'disable':
            $enabled = 0;
            break;
        default:
            $enabled = null;
    }
    $stmt = statement_factory::get_statements();
    $pluginlist = integritystmt::get_enabled_plugins();
    $pluginstmt = [];
    foreach ($pluginlist as $name) {
        $pluginstmt[$name] = $stmt[$name]->get_plugin_name();
    }
    [$insqlplugin, $paramsplugins] = $DB->get_in_or_equal(array_keys($pluginlist));
    $sql = "SELECT ct.id contextid, m.name modulename
                          FROM {modules} m
                    INNER JOIN {course_modules} cm ON m.id = cm.module
                    INNER JOIN {context} ct ON ct.instanceid = cm.id
                     LEFT JOIN {" . $integritytable . "} lis ON lis.contextid = ct.id
                         WHERE ct.contextlevel = 70
                           AND m.name $insqlplugin
                           AND lis.contextid IS NULL";
    $datacontextplugins = $DB->get_recordset_sql($sql, $paramsplugins);
    $batch = [];
    $batchsize = 2000;
    $time = time();
    $insertedcount = 0;
    foreach ($datacontextplugins as $datacontext) {
        $batch[] = (object) [
                'id' => null,
                'contextid' => $datacontext->contextid,
                'plugin' => $pluginstmt[$datacontext->modulename],
                'enabled' => $enabled,
                'usermodified' => 2,
                'timecreated' => $time,
                'timemodified' => $time,
        ];
        // Insert batch once it reaches the limit.
        if (count($batch) >= $batchsize) {
            $DB->insert_records($integritytable, $batch);
            $insertedcount += count($batch);
            $batch = []; // Clean memory.
        }
    }
    // Insert remaining records if any.
    if (!empty($batch)) {
        $DB->insert_records($integritytable, $batch);
        $insertedcount += count($batch);
    }
    $datacontextplugins->close();
    cli_writeln("Activity default setting of $enabled was set for $insertedcount activities");
}
exit(0);
