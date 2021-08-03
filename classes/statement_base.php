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
 * Base class for statements.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrity;

use stdClass;
use moodleform_mod;
use MoodleQuickForm;
use moodle_url;
use admin_settingpage;
use admin_setting_confightmleditor;
use admin_setting_heading;
use admin_setting_configselect;
use context_course;

defined('MOODLE_INTERNAL') || die;

/**
 * Base class for statements.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class statement_base {

    /**
     * Statement name.
     * @var string
     */
    protected $name;

    /**
     * Constructor.
     *
     * @param string $name Statement name.
     */
    final public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Force subclasses to define URL for triggering a statement.
     *
     * @return array
     */
    abstract protected function get_display_urls(): array;

    /**
     * Get statement test.
     *
     * @return string
     */
    final public function get_notice(): string {
        return get_config('integritystmt_' . $this->name, 'notice');
    }

    /**
     * Get statement test.
     *
     * @return int
     */
    final public function get_default_enabled(): int {
        return (int) get_config('integritystmt_' . $this->name, 'default_enabled');
    }

    /**
     * Check if statement can be applied.
     *
     * @param \context $context Context to check against.
     * @return bool
     */
    final public function can_change_default(\context $context): bool {
        return has_capability('integritystmt/' . $this->name . ':changedefault', $context);
    }

    /**
     * Check if we should apply statement on the given page URL.
     * @param \moodle_url $pageurl
     * @return bool
     */
    final public function should_display(moodle_url $pageurl): bool {
        foreach ($this->get_display_urls() as $url) {
            if (is_string($url)) {
                if ($pageurl->compare(new moodle_url($url), URL_MATCH_BASE)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get URL to redirect to after statement.
     *
     * @return string
     */
    public function get_decline_url(): string {
        global $COURSE;

        $url = new moodle_url('/course/view.php', ['id' => $COURSE]);

        return $url->out();
    }

    /**
     * Add sub plugin settings to the admin setting page for the plugin.
     *
     * @param \admin_settingpage $settings
     */
    public function add_settings(admin_settingpage $settings) {
        $settings->add(new admin_setting_heading(
                "integritystmt_{$this->name}/header",
                get_string('pluginname', "integritystmt_{$this->name}"),
                '')
        );

        $settings->add(new admin_setting_configselect(
                "integritystmt_{$this->name}/default_enabled",
                get_string('settings:default_enabled', 'local_integrity'),
                get_string('settings:default_enabled_description', 'local_integrity'),
                0,
                [
                    0 => get_string('no'),
                    1 => get_string('yes'),
                ]
            )
        );

        $settings->add(new admin_setting_confightmleditor(
                "integritystmt_{$this->name}/notice",
                get_string('settings:notice', 'local_integrity'),
                get_string('settings:notice_description', 'local_integrity'),
                '')
        );

        $settings->add(new \admin_setting_description(
                "integritystmt_{$this->name}/lastupdatedate",
                '',
                get_string('settings:lastupdatedated', 'local_integrity', $this->get_setting_last_updated_date('notice'))
            )
        );
    }

    /**
     * Get the last updated date for the given setting name.
     *
     * @param string $name Name of the setting.
     * @return string
     */
    final public function get_setting_last_updated_date(string $name): string {
        global $DB;

        $timemodified = $DB->get_field_sql('SELECT max(timemodified) FROM {config_log} WHERE plugin = :plugin AND name = :name', [
            'plugin' => 'integritystmt_' . $this->name,
            'name' => $name
        ]);

        if (!empty($timemodified)) {
            return userdate($timemodified);
        } else {
            return '-';
        }
    }

    /**
     * Extend course module form.
     *
     * @param \moodleform_mod $modform Mod form instance.
     * @param \MoodleQuickForm $form Form instance.
     */
    public function coursemodule_standard_elements(moodleform_mod $modform, MoodleQuickForm $form): void {
        $form->addElement('header', 'integrityheader', get_string('modform:header', 'local_integrity'));
        $form->addElement('selectyesno', 'integrity_enabled', get_string('modform:enabled', 'local_integrity'));
        $form->setDefault('integrity_enabled', $this->get_default_enabled());

        $cm = $modform->get_coursemodule();
        if ($cm) {
            if ($record = mod_settings::get_record(['cmid' => $cm->id])) {
                $form->setDefault('integrity_enabled', $record->get('enabled'));
            }
        }

        if (!$this->can_change_default(context_course::instance($modform->get_course()->id))) {
            $form->freeze(['integrity_enabled']);
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
    public function coursemodule_edit_post_actions(stdClass $moduleinfo, stdClass $course): stdClass {
        if (isset($moduleinfo->integrity_enabled)) {
            $cmid = $moduleinfo->coursemodule;
            $enabled = $moduleinfo->integrity_enabled;

            if ($record = mod_settings::get_record(['cmid' => $cmid])) {
                if ($record->get('enabled') != $enabled) {
                    $record->set('enabled', $enabled);
                    $record->save();
                }
            } else {
                $record = new mod_settings();
                $record->set('cmid', $cmid);
                $record->set('enabled', $enabled);
                $record->save();
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
    public function coursemodule_validation(moodleform_mod $modform, array $data): array {
        return [];
    }

}
