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

/**
 * Callbacks.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_integrity\statement_factory;
use local_integrity\mod_settings;

defined('MOODLE_INTERNAL') || die();

/**
 * Extend course module form.
 *
 * @param \moodleform_mod $modform Mod form instance.
 * @param \MoodleQuickForm $form Form instance.
 */
function local_integrity_coursemodule_standard_elements(moodleform_mod $modform, MoodleQuickForm $form): void {
    $cm = $modform->get_coursemodule();
    $modname = '';

    // Coerce modname from course module if we are updating existing module.
    if (!empty($cm) && !empty($cm->modname)) {
        $modname = $cm->modname;
    } else if (!empty($modform->get_current()->modulename)) {
        $modname = $modform->get_current()->modulename;
    }

    if (!empty($modname)) {
        $statement = statement_factory::get_statement($modname);
        if (!empty($statement)) {
            $statement->coursemodule_standard_elements($modform, $form);
        }
    }
}

/**
 * Extend course module form submission.
 *
 * @param \stdClass $moduleinfo Module info data.
 * @param \stdClass $course Course instance.
 *
 * @return \stdClass Mutated module info data.
 */
function local_integrity_coursemodule_edit_post_actions(stdClass $moduleinfo, stdClass $course): stdClass {
    if (!empty($moduleinfo->modulename)) {
        $statement = statement_factory::get_statement($moduleinfo->modulename);
        if (!empty($statement)) {
            $moduleinfo = $statement->coursemodule_edit_post_actions($moduleinfo, $course);
        }
    }

    return $moduleinfo;
}

/**
 * Extend course mod form validation.
 *
 * @param \moodleform_mod $modform Mod form instance.
 * @param array $data Submitted data.
 *
 * @return array
 */
function local_integrity_coursemodule_validation(moodleform_mod $modform, array $data): array {
    $errors = [];

    $cm = $modform->get_coursemodule();
    $modname = '';

    if (!empty($cm) && !empty($cm->modname)) {
        $modname = $cm->modname;
    } else if (!empty($modform->get_current()->modulename)) {
        $modname = $modform->get_current()->modulename;
    }

    if (!empty($modname)) {
        $statement = statement_factory::get_statement($modname);
        if (!empty($statement)) {
            $errors = $statement->coursemodule_validation($modform, $data);
        }
    }

    return $errors;
}

/**
 * Hook called before we delete a course module.
 *
 * @param \stdClass $cm The course module record.
 */
function local_integrity_pre_course_module_delete($cm) {
    if ($record = mod_settings::get_record(['cmid' => $cm->id])) {
        $record->delete();
    }
}
