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
 * Event observers used in pretest.
 *
 * @package    local_pretest
 * @copyright  2015 Joseph Gilgen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../lib.php');
/**
 * Event observer for pretest.
 */
class local_pretest_observer {

    /**
     * Triggered via quiz_attempt_submitted event.
     *
     * @param mod_quiz\event\attempt_submitted $event
     */
    public static function quiz_attempt_submitted(mod_quiz\event\attempt_submitted $event) {
        global $CFG,$DB;
        $attempts = $event->get_record_snapshot($event->objecttable, $event->objectid);
        $quizid = $event->other['quizid'];
        $pretest_grades = pretest_get_questions_grades($event->userid,$quizid,$attempts->attempt);
        $grade_items = pretest_get_items($event->courseid);
        pretest_update_grade($grade_items,$pretest_grades,$event->courseid,$event->userid);
    }

}
