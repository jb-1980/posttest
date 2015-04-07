<?php

defined('MOODLE_INTERNAL') || die();

/**
 * This function adds a posttest item to the course administration navigation tree
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function local_pretest_extends_settings_navigation(settings_navigation $navigation, context $context){
    global $CFG,$PAGE;
    if($PAGE->course->id > 1){
        if(has_capability('local/pretest:modify', $PAGE->context)){
            $pretest_node = $navigation->find('courseadmin', navigation_node::TYPE_COURSE)->add(
                              get_string('pretestsettings','local_pretest'),
                              new moodle_url($CFG->wwwroot.'/local/pretest/index.php',array('id'=>$PAGE->course->id)),
                              navigation_node::TYPE_SETTING,
                              null,null,
                              new pix_icon('i/settings','')
                          );
        }
    }
}

/**
 * This function is used to get all grades of the pretest questions in their 
 * fraction form to be used to calculate grades for gradebook items
 * @param int $userid the id of the user we are finding grades for
 * @param int $quizid the id of the pretest quiz
 * @return array
 */
function pretest_get_questions_grades($userid,$quizid,$attempt){
    global $DB,$CFG;

    $pfx = $CFG->prefix;

    $sql= "SELECT
        qa.questionid,
        qas.fraction
    FROM {$pfx}quiz_attempts quiza
    JOIN {$pfx}question_usages qu ON qu.id = quiza.uniqueid
    JOIN {$pfx}question_attempts qa ON qa.questionusageid = qu.id
    JOIN {$pfx}question_attempt_steps qas ON qas.questionattemptid = qa.id
    WHERE quiza.quiz = {$quizid}
          AND quiza.userid = {$userid}
          AND quiza.attempt = {$attempt}
          AND (qas.state = 'gradedright'
            OR qas.state = 'gradedwrong'
            OR qas.state = 'gaveup'
            OR qas.state = 'gradedpartial')
    ORDER by qa.id;";
    
    return $DB->get_records_sql($sql);
}

/**
 * 
 */
function pretest_get_items($courseid){
    global $DB;
    
    $data = $DB->get_records('local_pretest',array('courseid'=>$courseid));
    foreach($data as $k=>$v){
        $items[$v->gradeitemid][$v->questionid] = $v->weight;
    }
    return $items;
}

/**
 * This function is used to take all the test questions associated with a grade
 * item and calculate a grade for it.
 *
 * @param array $question_weights an array weights for each question
 * @return number, the value of the grade
 */
function pretest_update_grade($gradeitems,$question_grades,$courseid,$userid){
    global $DB;
    foreach($gradeitems as $id=>$weights){
        $sum=0;
        $N = 0;
        foreach($weights as $qid=>$weight){
            $q = $question_grades[$qid];
            $grade = $q->fraction ? $q->fraction : 0;
            $sum += $grade*$weight;
            $N += $weight;
        }
        $gradeitem = new grade_item(array('courseid'=>$courseid,'id'=>$id));
        $finalgrade = $gradeitem->grademax * $sum / $N;
        if($finalgrade){
            $gradeitem->update_raw_grade($userid, $finalgrade,$source=null,$feedback=get_string('feedback','local_pretest'));
        }

    }
}

/**
 * This function is called by 'usort' method to sort objects in array by property 'sortorder'
 *
 * @param grade_item $item1 object 1 to compare
 * @param grade_item $item2 object 2 to compare with object 1
 */
function pretest_sort_array_by_sortorder($item1, $item2) {
    if ($item1->sortorder == $item2->sortorder) {
        return 0;
    }
    return ($item1->sortorder < $item2->sortorder) ? -1 : 1;
}

/**
 * This function is called by 'usort' method to sort objects in array by property 'slot'
 *
 * @param grade_item $item1 object 1 to compare
 * @param grade_item $item2 object 2 to compare with object 1
 */
function pretest_sort_array_by_slot($item1, $item2) {
    if ($item1->slot == $item2->slot) {
        return 0;
    }
    return ($item1->slot < $item2->slot) ? -1 : 1;
}


