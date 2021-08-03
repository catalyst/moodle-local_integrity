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
    abstract protected function get_apply_urls(): array;

    /**
     * Get statement message.
     *
     * @return string
     */
    final public function get_message(): string {
        return get_config('integritystmt_' . $this->name, 'message');
    }

    /**
     * Check if statement can be applied.
     *
     * @param \context $context Context to check against.
     * @return bool
     */
    final public function can_apply(\context $context): bool {
        return has_capability('integritystmt/' . $this->name . ':apply', $context);
    }

    /**
     * Check if we should apply statement on the given page URL.
     * @param \moodle_url $pageurl
     * @return bool
     */
    final public function should_apply(moodle_url $pageurl): bool {
        foreach ($this->get_apply_urls() as $url) {
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
    public function get_redirect_url(): string {
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

        $settings->add(new admin_setting_confightmleditor(
                "integritystmt_{$this->name}/message",
                'Message',
                'Message Description',
                '')
        );
    }

    /**
     * Extend course module form.
     *
     * @param \moodleform_mod $modform Mod form instance.
     * @param \MoodleQuickForm $form Form instance.
     */
    public function coursemodule_standard_elements(moodleform_mod $modform, MoodleQuickForm $form): void {
        $form->addElement('header', 'integrityheader', 'Integrity');
        $form->addElement('selectyesno', 'integrity', 'Display integrity statement');

        $cm = $modform->get_coursemodule();
        if ($cm) {
            if ($record = mod_settings::get_record(['cmid' => $cm->id])) {
                $form->setDefault('integrity', $record->get('enabled'));
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
    public function coursemodule_edit_post_actions(stdClass $moduleinfo, stdClass $course): stdClass {
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
