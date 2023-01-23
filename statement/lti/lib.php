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
 * Lib functions.
 *
 * @package     integritystmt_lti
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_integrity\statement_factory;

/**
 * Call back executed after config loaded.
 */
function integritystmt_lti_after_config() {
    global $SCRIPT, $USER;

    if ($SCRIPT == '/mod/lti/launch.php') {
        $id = required_param('id', PARAM_INT);
        $triggerview = optional_param('triggerview', 1, PARAM_BOOL);
        $statement = statement_factory::get_statement('lti');

        if ($triggerview && $statement) {
            $context = context_module::instance($id);
            $statementenabled = $statement->is_enabled_in_context($context);

            if ($statementenabled && !$statement->can_bypass($context, $USER->id) && !$statement->is_agreed_by_user($context)) {
                redirect(new moodle_url('/local/integrity/statement/lti/launch.php', ['id' => $id, 'triggerview' => $triggerview]));
            }
        }
    }
}
