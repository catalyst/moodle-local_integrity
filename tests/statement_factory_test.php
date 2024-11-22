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

/**
 * Tests for statement factory.
 *
 * @package     local_integrity
 * @copyright   2021 Catalyst IT
 * @author      Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \local_integrity\statement_factory;
 */
class statement_factory_test extends advanced_testcase {

    /**
     * A list of known statements.
     * @var string[]
     */
    private $knownstatements = [
        'forum',
        'quiz',
        'lesson',
        'hsuforum',
        'data',
        'workshop',
        'hvp',
        'h5pactivity',
        'glossary',
        'lti',
        'scorm',
        'wiki',
        'turnitintooltwo',
        'assign',
    ];

    /**
     * Set up tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        parent::setUp();
    }

    /**
     * Test get statements.
     */
    public function test_get_statements() {
        $actual = statement_factory::get_statements();

        $this->assertCount(14, statement_factory::get_statements());
        $this->assertCount(count(statement_factory::get_statements()), $this->knownstatements);

        foreach ($this->knownstatements as $name) {
            $this->assertArrayHasKey($name, $actual);
            $this->assertInstanceOf('\\integritystmt_' . $name . '\\statement', $actual[$name]);
        }
    }

    /**
     * Test getting invalid statement.
     */
    public function test_get_invalid_statement() {
        $this->assertNull(statement_factory::get_statement('invalid'));
    }

    /**
     * Test getting valid statement.
     */
    public function test_getting_valid_statement() {
        foreach ($this->knownstatements as $name) {
            $actual = statement_factory::get_statement($name);
            $this->assertInstanceOf('\\integritystmt_' . $name . '\\statement', $actual);
        }
    }

    /**
     * Test that a list of enabled plugins is cached.
     */
    public function test_get_enabled_plugins_cached() {
        global $CFG;

        $cache = \cache::make('local_integrity', 'plugins');
        $this->assertFalse($cache->get($CFG->allversionshash));

        $expected = [];
        foreach (statement_factory::get_statements() as $name => $statement) {
            $expected[] = $name;
        }

        $this->assertSame($expected,  $cache->get($CFG->allversionshash));
    }

}
