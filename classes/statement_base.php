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
use context_module;

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
     * Integrity field name in an activity form.
     */
    const FORM_FIELD_NAME = 'integrity_enabled';

    /**
     * Statement name.
     * @var string
     */
    protected $name;

    /**
     * Plugin name.
     * @var string
     */
    protected $pluginname;

    /**
     * Constructor.
     *
     * @param string $name Statement name.
     */
    final public function __construct(string $name) {
        $this->name = $name;
        $this->pluginname = 'integritystmt_' . $this->name;
    }

    /**
     * Force subclasses to define URL for triggering a statement.
     *
     * @return array
     */
    abstract protected function get_display_urls(): array;

    /**
     * Get the name of the statement.
     *
     * @return string
     */
    final public function get_name(): string {
        return $this->name;
    }

    /**
     * Get the plugin name;
     *
     * @return string
     */
    final public function get_plugin_name(): string {
        return $this->pluginname;
    }

    /**
     * Check if the statement was agreed by a given user in the given context.
     *
     * @param \context $context Context to check.
     * @param int|null $userid User ID. If null the current user will be used.
     *
     * @return bool
     */
    final public function is_agreed_by_user(\context $context, ?int $userid = null): bool {
        global $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }
        if (empty($userid)) {
            return false;
        }

        return $this->get_user_data()->is_context_id_exist($context->id, $userid);
    }

    /**
     * Get user data for the plugin.
     *
     * @return \local_integrity\userdata_interface
     */
    public function get_user_data(): userdata_interface {
        return new userdata_default($this->get_plugin_name());
    }

    /**
     * Get statement test.
     *
     * @return string
     */
    final public function get_notice(): string {
        return get_config($this->get_plugin_name(), 'notice');
    }

    /**
     * Get statement test.
     *
     * @return int
     */
    final public function get_default_enabled(): int {
        return (int) get_config($this->get_plugin_name(), 'default_enabled');
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
    protected function should_display_for_url(moodle_url $pageurl): bool {
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
     * Check if the statement is enabled in the given context.
     *
     * @param \context $context
     * @return bool
     */
    protected function is_enabled_in_context(\context $context): bool {
        // TODO: add caching.
        return !empty(settings::get_record([
            'contextid' => $context->id,
            'plugin' => $this->get_plugin_name(),
            'enabled' => 1
        ]));
    }

    /**
     * Check if we should display statement for the user on the given page.
     *
     * @param \moodle_page $page Moodle page.
     * @param int|null $userid Given user ID.
     *
     * @return bool
     */
    public function should_display(\moodle_page $page, ?int $userid = null): bool {
        global $USER;

        if (empty($userid) && !empty($USER->id)) {
            $userid = $USER->id;
        }

        if (empty($userid)) {
            return false;
        }

        if (!$page->has_set_url()) {
            return false;
        }

        if (!$this->should_display_for_url($page->url)) {
            return false;
        }

        if ($this->is_enabled_in_context($page->context)) {
            // TODO:
            // 1. Check can bypass permissions.
            return !$this->is_agreed_by_user($page->context, $userid);
        }

        return false;
    }

    /**
     * Display statement on given page.
     */
    public function display_statement() {
        global $PAGE;

        if ($PAGE->context->contextlevel == CONTEXT_MODULE && !empty($PAGE->cm->modname)) {
            $PAGE->requires->js_call_amd('local_integrity/statement', 'init', [
                $PAGE->context->id,
                $this->get_name(),
                $this->get_decline_url()
            ]);
        }
    }

    /**
     * Get URL to redirect to after statement.
     *
     * @return string
     */
    public function get_decline_url(): string {
        global $COURSE;

        $result = '';

        if (!empty($COURSE->id)) {
            $url = new moodle_url('/course/view.php', ['id' => $COURSE->id]);
            $result = $url->out();
        }

        return $result;
    }

    /**
     * Add sub plugin settings to the admin setting page for the plugin.
     *
     * @param \admin_settingpage $settings
     */
    final public function add_settings(admin_settingpage $settings) {
        $settings->add(new admin_setting_heading(
                "{$this->get_plugin_name()}/header",
                get_string('pluginname', $this->get_plugin_name()),
                '')
        );

        $settings->add(new admin_setting_configselect(
                "{$this->get_plugin_name()}/default_enabled",
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
                "{$this->get_plugin_name()}/notice",
                get_string('settings:notice', 'local_integrity'),
                get_string('settings:notice_description', 'local_integrity'),
                '')
        );

        $settings->add(new \admin_setting_description(
                "{$this->get_plugin_name()}/lastupdatedate",
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
            'plugin' => $this->get_plugin_name(),
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
        $form->addElement('selectyesno', self::FORM_FIELD_NAME, get_string('modform:enabled', 'local_integrity'));
        $form->setDefault(self::FORM_FIELD_NAME, $this->get_default_enabled());

        $cm = $modform->get_coursemodule();
        if ($cm) {
            $context = context_module::instance($cm->id);

            if ($record = settings::get_record(['contextid' => $context->id, 'plugin' => $this->get_plugin_name()])) {
                $form->setDefault(self::FORM_FIELD_NAME, $record->get('enabled'));
            }
        }

        if (!$this->can_change_default(context_course::instance($modform->get_course()->id))) {
            $form->freeze([self::FORM_FIELD_NAME]);
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
        if (isset($moduleinfo->{self::FORM_FIELD_NAME})) {
            $context = context_module::instance($moduleinfo->coursemodule);
            $enabled = $moduleinfo->{self::FORM_FIELD_NAME};

            if ($record = settings::get_record(['contextid' => $context->id, 'plugin' => $this->get_plugin_name()])) {
                if ($record->get('enabled') != $enabled) {
                    $record->set('enabled', $enabled);
                    $record->save();
                }
            } else {
                $record = new settings();
                $record->set('contextid', $context->id);
                $record->set('enabled', $enabled);
                $record->set('plugin', $this->get_plugin_name());
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
