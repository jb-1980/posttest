<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../question/engine/questionusage.php');
require_once('lib.php');
global $DB,$CFG;

print_object(pretest_get_questions_grades(3,2,10));
