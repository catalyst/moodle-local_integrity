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
 * CLI script for resetting statements for users.
 *
 * @package    local_integrity
 * @copyright  2021 Catalyst IT
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'all' => false,
        'courseids' => false,
        'cmids' => false,
        'userids' => false,
        'plugins' => false,
        'help' => false
    ],
    [
        'a' => 'all',
        'c' => 'courseids',
        'm' => 'cmids',
        'u' => 'userids',
        'p' => 'plugins',
        'h' => 'help'
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
 -h, --help                Print out this help
 -a, --all                 Reset all statements for all users in the system.
 -c, --courseids           Comma delimited list of course IDs to reset statements for.
 -m, --cmids               Comma delimited list of course module IDs to reset statements for.
 -u, --userids             Comma delimited list of user IDs to reset all statements for.
 -p, --plugins             Comma delimited list of statement plugins to reset data for.

Example:
\$sudo -u www-data /usr/bin/php local/integrity/cli/reset.php --all
\$sudo -u www-data /usr/bin/php local/integrity/cli/reset.php --courseids=1,16
\$sudo -u www-data /usr/bin/php local/integrity/cli/reset.php --cmids=5,17,14
\$sudo -u www-data /usr/bin/php local/integrity/cli/reset.php --userids=2,19
\$sudo -u www-data /usr/bin/php local/integrity/cli/reset.php --plugins=integritystmt_forum,integritystmt_assign


EOT;
    cli_writeln($help);
    exit(0);
}

if ($options['all']) {
    $DB->delete_records(\local_integrity\userdata_default::TABLE);
} else if (!empty($options['courseids'])) {
    $courseids = explode(',', $options['courseids']);

    array_walk($courseids, function($courseid) {
        return trim($courseid);
    });

    foreach ($courseids as $courseid) {
        $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
        if (!empty($coursecontext)) {
            $children = $coursecontext->get_child_contexts();
            if (!empty($children)) {
                list($insql, $params) = $DB->get_in_or_equal(array_keys($children));
                $DB->delete_records_select(\local_integrity\userdata_default::TABLE, "contextid $insql", $params);
            }
        }
    }
} else if (!empty($options['cmids'])) {
    $cmids = explode(',', $options['cmids']);
    foreach ($cmids as $cmid) {
        $context = context_module::instance($cmid, IGNORE_MISSING);
        if (!empty($context)) {
            $DB->delete_records(\local_integrity\userdata_default::TABLE, ['contextid' => $context->id]);
        }
    }
} else if (!empty($options['userids'])) {
    $userids = explode(',', $options['userids']);
    list($insql, $params) = $DB->get_in_or_equal($userids);
    $DB->delete_records_select(\local_integrity\userdata_default::TABLE, "userid $insql", $params);
} else if (!empty($options['plugins'])) {
    $plugins = explode(',', $options['plugins']);
    list($insql, $params) = $DB->get_in_or_equal($plugins);
    $DB->delete_records_select(\local_integrity\userdata_default::TABLE, "plugin $insql", $params);
} else {
    cli_writeln("Command must include one option of 'all', 'courseids', 'cmids' or 'userids'.");
    exit(1);
}

\cache::make('local_integrity', 'userdata')->purge();
cli_writeln("Done!");
exit(0);
