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
 * @copyright  2021 Catalyst IT
 * @author     Guillaume BARAT (guillaumebarat@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_integrity\plugininfo\integritystmt;
use local_integrity\statement_factory;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'default' => false,
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

if ($options['help']) {
    $help = <<<EOT
Reset integrity statement agreements.

Options:
 -d, --default             Set default integrity settings for each activity that has not been set yet.

Example:
\$sudo -u www-data /usr/bin/php local/integrity/cli/default.php --default=0,1

EOT;
    cli_writeln($help);
    exit(0);
}

if (isset($options['default']) && in_array($options['default'], [0, 1])) {
    $enabled = $options['default'];
    $stmt = statement_factory::get_statements();
    $pluginlist = integritystmt::get_enabled_plugins();
    foreach ($pluginlist as $name) {
        $pluginstmt[$name] = $stmt[$name]->get_plugin_name();
    }
    [$insqlplugin, $paramsplugins] = $DB->get_in_or_equal(array_keys($pluginlist));
    $courseids = $DB->get_records('course', null, 'id', 'id');
    foreach ($courseids as $courseid) {
        $coursecontext = context_course::instance($courseid->id, IGNORE_MISSING);
        if (!empty($coursecontext)) {
            $children = $coursecontext->get_child_contexts();
            if (!empty($children)) {
                [$insql, $params] = $DB->get_in_or_equal(array_keys($children));
                $sql = "SELECT ct.id contextid, m.name modulename
                            FROM {modules} m
                            INNER JOIN {course_modules} cm ON m.id = cm.module
                            INNER JOIN {context} ct ON ct.instanceid = cm.id
                            WHERE ct.contextlevel = 70 AND cm.course = $courseid->id AND m.name $insqlplugin
                            ";
                $check = $DB->get_records_select(\local_integrity\settings::TABLE, "contextid $insql", $params);
                $contextalreadysettup = [];
                $datacontextplugins = $DB->get_records_sql($sql, $paramsplugins);
                foreach ($check as $activity) {
                    $contextalreadysettup[] = $activity->contextid;
                }
                $buildobject = [];
                foreach ($datacontextplugins as $datacontext) {
                    if (!in_array($datacontext->contextid, $contextalreadysettup)) {
                        $buildobject[] = (object) [
                                'id' => null,
                                'contextid' => $datacontext->contextid,
                                'plugin' => $pluginstmt[$datacontext->modulename],
                                'enabled' => $enabled,
                                'usermodified' => 2,
                                'timecreated' => time(),
                                'timemodified' => time(),
                        ];
                    }
                }
                $DB->insert_records(\local_integrity\settings::TABLE, $buildobject);
            }
        }
    }
    cli_writeln("Activity default setting all set.");
}
exit(0);
