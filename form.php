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

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once('lib.php');
/**
 * The form for editing the pretest settings in a course.
 *
 * @copyright 2015 Joseph Gilgen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_pretest_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE, $DB;
        $mform = $this->_form;
        // Context instance of the course.
        $coursecontext = context_course::instance($COURSE->id);

        // Display Pretest
        $pretest = $DB->get_record('quiz',array('id'=>$this->_customdata['quiz']));
        $pretest_name = get_string('pretestname','local_pretest');
        $changeurl = new moodle_url('/local/pretest/index.php', array('id' => $COURSE->id,'update'=>1));
        $btn = get_string('changetest','local_pretest');
        $html = "<p class='fheader'>{$pretest_name}: {$pretest->name} <a class='pretest-button-link' href='{$changeurl}'>{$btn}</a></p>";
        $mform->addElement('html',$html);
        // Fetching Gradebook items.
        $gradeitems = grade_item::fetch_all(array('courseid' => $COURSE->id));
        $questions = $DB->get_records('quiz_slots',array('quizid'=>$pretest->id));
        if (is_array($gradeitems) && (count($gradeitems) >1)) {
            usort($gradeitems, 'pretest_sort_array_by_sortorder');
            usort($questions,'pretest_sort_array_by_slot');
            
            foreach ($gradeitems as $gradeitem) {
                // Skip course and category grade items and the pretest.
                if ($gradeitem->itemtype == "course" or 
                    $gradeitem->itemtype == "category" or
                    ($gradeitem->itemtype == "mod" and $gradeitem->itemmodule == 'quiz' and $gradeitem->iteminstance == $pretest->id)) {
                    continue;
                }
                // Leave out grade items that are none type
                if (!$gradeitem->gradetype){
                    continue;
                }
                
                $mform->addElement('header', 'gradebookitemsheader'.$gradeitem->id,
                    $gradeitem->itemname);
                $mform->setExpanded('gradebookitemsheader'.$gradeitem->id, false);
                
                foreach($questions as $question){
                    $label = $DB->get_record('question',array('id'=>$question->questionid));
                    $mform->addElement('text',
                        'gradeitem['.$gradeitem->id.'][qid]['.$question->questionid.']',
                        $label->name,
                        array('style'=>'width:50px;'));
                    $mform->setType('gradeitem['.$gradeitem->id.'][qid]['.$question->questionid.']',PARAM_RAW);
                }
            }
        }

        $this->add_action_buttons();

    }
}

/**
 * The form for editing the pretest settings in a course.
 *
 * @copyright 2015 Joseph Gilgen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_pretest_set_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE, $DB;
        $mform = $this->_form;
        // Context instance of the course.
        $coursecontext = context_course::instance($COURSE->id);

        $quizzes = $DB->get_records('quiz',array('course'=>$COURSE->id));
        foreach($quizzes as $quiz){
            //print_object($quiz);
            $select[$quiz->id]=$quiz->name;
        }
        
        if($default = $DB->get_record('local_pretest_coursemap',array('courseid'=>$COURSE->id))){
            $name = $default->quizid;
        } else{
            $name = '';
        }
        
        $mform->addElement('select', 'quizzes', get_string('choosequiz', 'local_pretest'),$select ,array('height'=>'64px','overflow'=>'hidden','width'=>'240px','data-placeholder'=>$name));
        $mform->addHelpButton('quizzes', 'choosequiz', 'local_pretest');

        $this->add_action_buttons();
        
    }
}


