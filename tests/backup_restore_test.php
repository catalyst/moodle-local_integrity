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

namespace local_integrity;

use advanced_testcase;
use backup;
use restore_dbops;
use backup_controller;
use restore_controller;
use context_module;

/**
 * Tests for backup and restore functions.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \backup_local_integrity_plugin
 * @covers \restore_local_integrity_plugin
 */
class backup_restore_test extends advanced_testcase {
    /**
     * Course instance for testing.
     * @var
     */
    protected $course;

    /**
     * Set up tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        parent::setUp();
    }

    /**
     * A helper method to backup a course.
     *
     * @return string A backup ID ready to be restored.
     */
    protected function backup_course(): string {
        global $CFG, $USER;

        // Get the necessary files to perform backup and restore.
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $backupid = 'test-local-integrity-backup';

        $bc = new backup_controller(backup::TYPE_1COURSE, $this->course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);
        $bc->execute_plan();

        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        return $backupid;
    }

    /**
     * Restore a course from provided backup id.
     *
     * @param string $backupid backup ID.
     * @return int
     */
    protected function restore_course(string $backupid): int {
        global $USER;

        $newcourseid = restore_dbops::create_new_course('Test', 'test', $this->course->category);

        $rc = new restore_controller($backupid, $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id,
            backup::TARGET_NEW_COURSE);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }

    /**
     * Test duplicating an activity.
     */
    public function test_duplicate_activity() {
        $this->setAdminUser();

        $plugin = 'test';

        $this->course = $this->getDataGenerator()->create_course();
        $mod = $this->getDataGenerator()->create_module('forum', ['course' => $this->course->id]);
        $contextid = context_module::instance($mod->cmid)->id;

        $this->assertEquals(0, settings::count_records());

        $settings = new settings();
        $settings->set('contextid', $contextid);
        $settings->set('plugin', $plugin);
        $settings->set('enabled', 1);
        $settings->save();

        $this->assertEquals(1, settings::count_records());

        $newcm = duplicate_module($this->course, get_fast_modinfo($this->course)->get_cm($mod->cmid));
        $this->assertEquals(2, settings::count_records());

        $newcontextid = context_module::instance($newcm->id)->id;
        $actual = settings::get_settings($plugin, $newcontextid);

        $this->assertEquals($newcontextid, $actual->get('contextid'));
        $this->assertEquals($settings->get('plugin'), $actual->get('plugin'));
        $this->assertEquals($settings->get('enabled'), $actual->get('enabled'));
    }

    public function test_backup_restore_course() {
        $this->setAdminUser();

        $this->course = $this->getDataGenerator()->create_course();
        $mod = $this->getDataGenerator()->create_module('forum', ['course' => $this->course->id]);
        $contextid = context_module::instance($mod->cmid)->id;

        $this->assertEquals(0, settings::count_records());

        $plugin = 'test';
        $settings = new settings();
        $settings->set('contextid', $contextid);
        $settings->set('plugin', $plugin);
        $settings->set('enabled', 1);
        $settings->save();

        $this->assertEquals(1, settings::count_records());

        $backupid = $this->backup_course();
        $newcourseid = $this->restore_course($backupid);

        $this->assertEquals(2, settings::count_records());
    }
}
