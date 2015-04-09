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
 * Script to edit settings for a pretest to award credit to assignments based on
 * pretest performance.
 *
 * @package   local_pretest
 * @copyright 2015 Joseph Gilgen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(dirname(__FILE__) . '/form.php');

$id = required_param('id', PARAM_INT); // Course id.
$update = optional_param('update',0,PARAM_BOOL);

// Should be a valid course id.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);


// Setup page.
$PAGE->set_url('/local/pretest/index.php', array('id'=>$id));
$PAGE->set_pagelayout('admin');

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('local/pretest:modify', $coursecontext);
$returnurl = new moodle_url('/course/view.php', array('id' => $id));
$quiz = $DB->get_record('local_pretest_coursemap',array('courseid'=>$id,'userid'=>$USER->id));
if(!$quiz || $update){
    // Creating form instance, passed course id as parameter to action url.
    $baseurl = new moodle_url('/local/pretest/index.php', array('id' => $id,'update'=>1));
    $rurl = new moodle_url('/local/pretest/index.php', array('id' => $id));
    $mform = new local_pretest_set_form($baseurl);    
    if ($mform->is_cancelled()) {
        // Redirect to course view page if form is cancelled.
        redirect($rurl);
    } else if ($data = $mform->get_data()) {
        if(!$record = $DB->get_record('local_pretest_coursemap',array('courseid'=>$id,'userid'=>$USER->id))){
              $dataobject = new stdClass;
              $dataobject->quizid = $data->quizzes;
              $dataobject->courseid = $id;
              $dataobject->userid = $USER->id;
              $DB->insert_record('local_pretest_coursemap',$dataobject);
            } else{
                $record->quizid = $data->quizzes;
                $DB->update_record('local_pretest_coursemap', $record);
            }
        redirect($rurl);
    } else {
        $PAGE->set_title($course->shortname .': '. get_string('pretestsettings', 'local_pretest'));
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($course->fullname));
        $mform->display();
        echo $OUTPUT->footer();
    }
    
} else{

    // Creating form instance, passed course id as parameter to action url.
    $baseurl = new moodle_url('/local/pretest/index.php', array('id' => $id));
    $mform = new local_pretest_form($baseurl,array('quiz'=>$quiz->quizid));
    
    if ($mform->is_cancelled()) {
        // Redirect to course view page if form is cancelled.
        redirect($returnurl);
    } else if ($data = $mform->get_data()) {
        //print_object($data);
        $gradeitems = $data->gradeitem;
        
        $transaction = $DB->start_delegated_transaction();
        foreach($gradeitems as $itemid=>$questions){
            foreach($questions['qid'] as $qid=>$weight){
                if($weight){
                    $params = array('questionid'=>$qid,'gradeitemid'=>$itemid);
                    if(!$record = $DB->get_record('local_pretest',$params)){
                        $dataobject = new stdClass;
                        $dataobject->gradeitemid = $itemid;
                        $dataobject->courseid = $id;
                        $dataobject->quizid = $quiz->quizid;
                        $dataobject->questionid = $qid;
                        $dataobject->weight = $weight;
                        $DB->insert_record('local_pretest',$dataobject);
                    } else{
                        $record->weight = $weight;
                        $DB->update_record('local_fd_mod_duration', $record);
                    }
                }
            }
            
        }

        // Commit transaction.
        $transaction->allow_commit();
        rebuild_course_cache($course->id);
        redirect($returnurl);
    } else {
        $PAGE->set_title($course->shortname .': '. get_string('pretestsettings', 'local_pretest'));
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        $mform->display();
        echo $OUTPUT->footer();
    }
}
