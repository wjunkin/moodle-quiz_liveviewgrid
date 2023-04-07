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
 * Internal library of functions for module quiz_liveview
 *
 * @package   quiz_liveviewgrid
 * @copyright  2018 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//$multisingle=1; //Twingsister to be considered multisingle is missing 
/**
 * Return the number of users who have submitted answers to this quiz instance.
 *
 * @param int $quizid The ID for the quiz instance.
 * @return array The userids for all the students submitting answers.
 */
function liveview_who_sofar_gridview($quizid) {
    global $DB;

    $records = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
    $studentrole = $DB->get_record('role', array('shortname' => 'student'));
    $quiz = $DB->get_record('quiz', array('id' => $quizid));
    $context = context_course::instance($quiz->course);
    foreach ($records as $record) {
        if ($DB->get_record('role_assignments', array('contextid' => $context->id,
            'roleid' => $studentrole->id, 'userid' => $record->userid))) {
            $userid[] = $record->userid;
        }
    }
    if (isset($userid)) {
        return(array_unique($userid));
    } else {
        $userid = array();
        return $userid;
    }
}

/**
 * Return the number of users who have submitted answers to this lesson instance.
 *
 * @param int $lessonid The ID for the lesson instance.
 * @return array The userids for all the students submitting answers.
 */
function liveview_who_sofar_lesson($lessonid) {
    global $DB;

    $records = $DB->get_records('lesson_attempts', array('lessonid' => $lessonid));
    $studentrole = $DB->get_record('role', array('shortname' => 'student'));
    $lesson = $DB->get_record('lesson', array('id' => $lessonid));
    $context = context_course::instance($lesson->course);
    foreach ($records as $record) {
        if ($DB->get_record('role_assignments', array('contextid' => $context->id,
            'roleid' => $studentrole->id, 'userid' => $record->userid))) {
            $userid[] = $record->userid;
        }
    }
    if (isset($userid)) {
        return(array_unique($userid));
    } else {
        return(null);
    }
}

/**
 * Return the first and last name of a student.
 *
 * @param int $userid The ID for the student.
 * @return string The last name, first name of the student.
 */
function liveview_find_student_gridview($userid) {
     global $DB;
     $user = $DB->get_record('user', array('id' => $userid));
     $name = $user->firstname." ".$user->lastname;
     return($name);
}

/**
 * Function to return the code for a single question button with tooltip.
 *
 * @param string $buttontext The text for the button.
 * @param string $hidden The array of the current hidden values.
 * @param string $linkid The key for the button tooltip.
 * @return string. The html code for the button form.
 */
function liveview_question_button($buttontext, $hidden, $linkid) {
    global $CFG;
    $mytext = "\n<form action=\"".$CFG->wwwroot."/mod/quiz/report.php\" target=\"_parent\">";
    foreach ($hidden as $key => $value) {
        $mytext .= "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
    }
    $mytext .= "<div class=\"showTip $linkid\">";
    $mytext .= "<input class='btn btn-primary' type=\"submit\" value=\"".htmlentities($buttontext)."\"></form>";
    $mytext .= "</div>";
    return $mytext;
}

/**
 * A function to create a dropdown menu for the groups.
 *
 * @param int $courseid The id for the course.
 * @param string $geturl The url for the form when submit is clicked.
 * @param int $canaccess Whether the user can (1) or cannot (0) access all groups.
 * @param array $hidden The array of keys and values for the hidden inputs in the form.
 */
function liveviewgrid_group_dropdownmenu($courseid, $geturl, $canaccess, $hidden) {
    global $DB, $USER;
    echo "\n<table border=0><tr><td valign=\"top\">";
    echo get_string('whichgroups', 'quiz_liveviewgrid')."</td>";
    $groups = $DB->get_records('groups', array('courseid' => $courseid));
    echo "\n<td><form action=\"$geturl\">";
    $mygroup = -1;
    foreach ($hidden as $key => $value) {
        if ($key <> 'group') {
            echo "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        } else {
            $mygroup = $value;
        }
    }
    echo "\n<select name=\"group\" onchange='this.form.submit()'>";
    echo "\n<option value=\"0\">".get_string('choosegroup', 'quiz_liveviewgrid')."</option>";
    if ($canaccess && ($mygroup > 0)) {
        echo "\n<option value=\"0\">".get_string('allgroups', 'quiz_liveviewgrid')."</option>";
    }
    foreach ($groups as $grp) {
        if ($DB->get_record('groups_members', array('groupid' => $grp->id, 'userid' => $USER->id)) || $canaccess) {
            $groupid = $grp->id;
            // This teacher can see this group.
            if ($groupid <> $mygroup) {
                $okgroup[$groupid] = $grp->name;
            }
        }
    }
    asort($okgroup);
    foreach ($okgroup as $grpid => $grpname) {
        echo "\n<option value=\"$grpid\">$grpname</option>";
    }
    echo "\n</select>";
    echo "\n</form></td></tr></table>";
}

/**
 * A function to create a dropdown menu for the questions.
 * @param int $quizid The id for the quiz.
 * @param string $geturl The url for the form when submit is clicked.
 * @param array $hidden The array of keys and values for the hidden inputs in the form.
 * @param int $quizcontextid The id of the context for this quiz.
 */
function liveviewgrid_question_dropdownmenu($quizid, $geturl, $hidden, $quizcontextid) {
    global $DB, $USER;
    echo "\n<table border=0><tr><td>";
    echo get_string('whichquestion', 'quiz_liveviewgrid')."</td>";
    $slots = liveviewslots($quizid, $quizcontextid);
    echo "\n<td><form action=\"$geturl\">";
    $mysingleqid = 0;
    foreach ($hidden as $key => $value) {
        if ($key <> 'singleqid') {
            echo "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        } else {
            $mysingleqid = $value;
        }
    }
    echo "\n<select name=\"singleqid\" onchange='this.form.submit()'>";
    echo "\n<option value=\"-1\">".get_string('choosequestion', 'quiz_liveviewgrid')."</option>";
    if ($mysingleqid) {
        echo "\n<option value=\"0\">".get_string('allquestions', 'quiz_liveviewgrid')."</option>";
    }
    foreach ($slots as $slotkey => $slot) {
        $question = $DB->get_record('question', array('id' => $slotkey));
        if ($question->id <> $mysingleqid) {
            $questionname = $question->name;
            if (strlen($questionname) > 80) {
                $questionname1 = substr($questionname, 0, 80).'....';
            } else {
                $questionname1 = $questionname;
            }
            echo "\n<option value=\"".$question->id."\">".$questionname1."</option>";
        }
    }
    echo "\n</select>";
    echo "\n</form></td>";
    if ($mysingleqid) {
        echo "<td>";
        echo "<form action=\"$geturl\">";
        foreach ($hidden as $key => $value) {
            if ($key <> 'singleqid') {
                echo "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
            } else {
                echo "\n<input type=\"hidden\" name=\"singleqid\" value=\"0\">";
            }
        }
        echo "<input type=\"submit\" value=\"".get_string('allquestions', 'quiz_liveviewgrid')."\">";
        echo "</form>";
        echo "</td>";
    }
    echo "</tr></table>";
}
function ggbTotal(array $answers,string $resp){
 //$j = 0;
 $fraction = 0;
 $summary = '';
 if (str_contains($resp, ':')) {
    $values = explode("%",$resp); // Twingsister
    $resmap = array();
    foreach ($values as $ans) {
        $tmp=explode(':',$ans);
        $resmap[$tmp[0]]=$tmp[1];
    }
 } else {
     $i=0;
     foreach ($answers as $answer) {
         $resmap[$answer->answer]=($resp[$i]=='0'?'false':'true');
         $i++;
         // string $resp is like "0110"
     }
         
 } //old qtype
 if(count($resmap)!=count($answers)){
     $fraction=1.0;
     $summary='mismatch answers';
 } else {
    foreach ($answers as $answer) {
        //  add a comma if necessary
        if ($summary !== '') {
            $summary .= ', ';
        }
        // the name of the variable
        $summary .= $answer->answer . '=';
        //$responseclass .= $answer->answer . '=' . $values[$j];
        // contribution to the result
        $valnum = (array_key_exists($answer->answer,$resmap)?$resmap[$answer->answer]:'1');
        $valnum =  ($valnum == "true"?1:($valnum == "false"?0:floatval($valnum)));
        $fraction += ($answer->fraction)*$valnum;
        $summary .= sprintf("%.2f",$valnum) . ',' .
            get_string('grade', 'grades') . ': ' .
            sprintf("%.2f",$answer->fraction);
            //$summary .= format_float($valnum, 2, false, false) . ',' .
            //            get_string('grade', 'grades') . ': ' .
            //            format_float($answer->fraction, 2, false, false);
            //$j++;
    }
    if ($fraction > 1) {
        $fraction = 1;
    }
    $summary .= '; ' . get_string('total', 'grades') . ': ' . $fraction;
 }
    return array('summary'=>$summary, 'fraction'=>$fraction);//,'responseclass'=>$responseclass);
} 
/**
 * A function to return the most recent response of all students to the questions in a quiz and the grade for the answers.
 *
 * @param int $quizid The id for the quiz.
 * @return array $returnvalues. $returnvalues[0] = $stanswers[$stid][$qid], $returnvalues[1] = $stfraction[$stid][$qid].
 **/
function liveviewgrid_get_answers($quizid) {
    global $DB;
    $role = $DB->get_record('role', array('shortname' => 'student'));
    $studentroleid = $role->id;
    $quiz = $DB->get_record('quiz', array('id' => $quizid));
    $courseid = $quiz->course;
    $coursecontext = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $courseid));
    $coursecontextid = $coursecontext->id;
    $singleqid = optional_param('singleqid', 0, PARAM_INT);
    $group = optional_param('group', 0, PARAM_INT);
    $whereqid = '';
    if ($singleqid > 0) {
        $whereqid = "AND qa.questionid = $singleqid";
    }
    if ($group > 0) {
        $groupjoin = " JOIN {groups_members} gm ON qza.userid = gm.userid ";
        $wheregroup = "AND gm.groupid = $group";
    } else {
        $groupjoin = '';
        $wheregroup = '';
    }
        $sqldata = "SELECT qasd.*, qa.questionid, qza.userid, qza.uniqueid, qas.state, qa.slot, qa.questionsummary
        FROM {question_attempt_step_data} qasd
        JOIN {question_attempt_steps} qas ON qasd.attemptstepid = qas.id
        JOIN {question_attempts} qa ON qas.questionattemptid = qa.id
        JOIN {quiz_attempts} qza ON qa.questionusageid = qza.uniqueid
        JOIN {role_assignments} ra ON qza.userid = ra.userid
        $groupjoin
        WHERE qza.quiz = $quizid AND ra.roleid = $studentroleid AND ra.contextid = $coursecontextid $whereqid $wheregroup";
    $params = array();
    $data = $DB->get_records_sql($sqldata, $params);

    // These arrays are the 'answr' or 'fraction' or 'link' (for attachments) indexed by userid and questionid.
    $stanswers = array();
    $stfraction = array();
    $ggbcode= array(); //Twingsister collect ggb last saved status
    $stlink = array();
    // The array for $data to multichoice questions with more than one answer (checkboxes).
    $datum = array();
    $multidata = array(); //Twingsister 
    foreach ($data as $key => $datum) {
        $usrid = $datum->userid;
        $qubaid = $datum->uniqueid;
        $mydm = new quiz_liveviewgrid_fraction($qubaid);
        $question = $DB->get_record('question', array('id' => $datum->questionid));
        if ($question->qtype == 'geogebra') { // Twingsister
            //if ($datum->name == 'ggbbase64') {$ggbcode[$usrid][$datum->questionid]= $datum->value;}//Twingsister
            if ($datum->name == 'answer') {
            xdebug_break();
                // get all the ->answer the name of the variable ->fraction the fraction in the note ->feedback
                $ggbanswers =$DB->get_records('question_answers', array('question' => $datum->questionid), $sort='', $fields='*', $limitfrom=0, $limitnum=0);
                //$datum->value; contains a percent % separated list of answers
                $tot=ggbTotal($ggbanswers,$datum->value);
                $stanswers[$usrid][$datum->questionid] =$tot['summary'];//"A tooltip";  // TWINGSISTER DEBUG $datum->value;
                $stfraction[$usrid][$datum->questionid] =$tot['fraction']; //sets the color// $tfresponse->fraction;
            }
        } else if ($question->qtype == 'multichoice') {
            $multidata[$datum->id] = $datum;
            // I will deal with multichoice later.
        } else if (($question->qtype == 'essay') || ($question->qtype == 'shortanswer')) {
            if ($datum->name == 'answer') {
                $stanswers[$usrid][$datum->questionid] = $datum->value;
            }
        } else if ($question->qtype == 'truefalse') {
            if ($datum->name == 'answer') {
                $tfres = $DB->get_record('question_truefalse', array('question' => $datum->questionid));
                if ($datum->value == 1) {
                    $tfans = $tfres->trueanswer;
                } else {
                    $tfans = $tfres->falseanswer;
                }
                $tfresponse = $DB->get_record('question_answers', array('id' => $tfans));
                $stanswers[$usrid][$datum->questionid] = $tfresponse->answer;
                $stfraction[$usrid][$datum->questionid] = $tfresponse->fraction;
            }
        } else {
            $myresponse = array();
            if (($datum->state == 'complete') || ($datum->state == 'invalid')
                || ($datum->state == 'todo')) {
                // Handling Cloze questions, 'invalid' and immediatefeedback, 'todo'.
                $myresponse[$datum->name] = $datum->value;
                if ($question->qtype == 'match') {
                    // If a person answers a question more than once, the question_attempt_step->id changes.
                    if (!(isset($qtempt[$datum->attemptstepid]))) {
                        $qtempt[$datum->attemptstepid] = 1;
                        $matchgrade[$datum->attemptstepid] = 0;
                        $matchanswer[$datum->attemptstepid] = '';
                    }
                    $mymatch = array();
                    $subquestions = $DB->get_records('qtype_match_subquestions', array('questionid' => $question->id));
                    $myquestions = array();
                    foreach ($subquestions as $subid => $subquestion) {
                        $questiontext = preg_replace('/<p.+?>/', '', $subquestion->questiontext);
                        $mymatch[$subid]['qtext'] = preg_replace("/<\/p>/", '', $questiontext);
                        $mymatch[$subid]['atext'] = $subquestion->answertext;
                    }
                    if (isset($myresponse['_stemorder'])) {
                        $stems[$datum->questionid] = explode(',', $myresponse['_stemorder']);
                        $stemcount[$datum->questionid] = count($stems[$datum->questionid]);
                    }
                    if (isset($myresponse['_choiceorder'])) {
                        $mchoices[$datum->questionid] = explode(',', $myresponse['_choiceorder']);
                    }
                    if (count($stems[$datum->questionid]) > 0) {
                        foreach ($stems[$datum->questionid] as $stkey => $stvalue) {
                            $choicekey = 'sub'.$stkey;
                            if (isset($myresponse[$choicekey])) {
                                $mymchoice = $myresponse[$choicekey];
                                $stchoice = $mymchoice - 1;
                                // The choice array starts at 0 but values in question_attempt_step_data table starts at 1.
                                // The value of $stkey gives the place on the screen where the stem is displayed.
                                // Using this index in the stems array gives the id for the qtype_match_subquestions table.
                                // The choice selected from the mchoices comes from the index value of mymatch in this table.
                                $xchoice = $mchoices[$datum->questionid][$stchoice];
                                $matchanswer[$datum->attemptstepid] .= $mymatch[$stems[$datum->questionid][$stkey]]['qtext'].
                                    "->".$mymatch[$xchoice]['atext']."; ";
                                if ($stems[$datum->questionid][$stkey] == $xchoice) {
                                    $matchgrade[$datum->attemptstepid] ++;
                                }
                            }
                        }
                    }
                    $questionsummary = $datum->questionsummary;
                    preg_match_all('/{{.+?}}/s', $questionsummary, $mymatches);
                }
                if (($question->qtype == 'matrix') && ($datum->state <> 'todo')) {
                    if (!(isset($matrixresponse[$datum->attemptstepid]))) {
                        $matrixresponse[$datum->attemptstepid] = array();
                    }
                    $qmatrix = $DB->get_record('question_matrix', array('questionid' => $datum->questionid));
                    $rowlabels = $DB->get_records('question_matrix_rows', array('matrixid' => $qmatrix->id));
                    $numrows = count($rowlabels);
                    if (!(isset($matrixrowanswers[$qmatrix->id]))) {// This is the first time for this matrix.
                        // Get the correct columns for each row in the array matrixanswers[matrixid][rowid].
                        foreach ($rowlabels as $key => $rowlabel) {
                            $matrixanswers[$qmatrix->id][$key] = array();
                            $rowvalues = $DB->get_records('question_matrix_weights', array('rowid' => $key));
                            foreach ($rowvalues as $rkey => $rowvalue) {
                                if ($rowvalue->weight > 0.9) {
                                    array_push($matrixanswers[$qmatrix->id][$key], $rowvalue->colid);
                                }
                            }
                        }
                        $matrixrowanswers[$qmatrix->id] = 1;
                    }
                    if (($qmatrix->multiple == 1) && (!(isset($mans[$datum->attemptstepid])))) {
                        $mans[$datum->attemptstepid] = array();// An array for the checkbox answers, indexed by row and answer.
                        $myrow[$datum->attemptstepid] = array();// An array to keep track of the rowids.
                        $qtext[$datum->attemptstepid] = array();// An array for the text for each row.
                        $myweight[$datum->attemptstepid] = array();
                    }
                    // I need to find the column labels, row texts, and correct answer for each row of this matrix question.
                    // Get text for each row, subscripted by the row id.
                    $rowtext = array();
                    foreach ($rowlabels as $key => $rowlabel) {
                        $rowtext[$rowlabel->id] = $rowlabel->shorttext;
                        if (!(isset($myweight[$datum->attemptstepid][$key]))) {
                            $myweight[$datum->attemptstepid][$key] = array();
                        }
                    }
                    foreach ($myresponse as $key => $respon) {
                        if ($qmatrix->multiple == 1) {
                            // Checkboxes in this matrix question.
                            if (preg_match('/cell(\d+)_(\d+)/', $key, $matches)) {
                                $rowid = $matches[1];
                                $myrow[$rowid] = 1;
                                $colid = $matches[2];
                                $weight = 0;
                                if (($rowid > 0) && ($colid > 0)) {
                                    $parms = array('rowid' => $rowid, 'colid' => $colid);
                                    if ($fract = $DB->get_record('question_matrix_weights', $parms)) {
                                        $weight = $fract->weight;
                                        if ($weight > 0.9) {
                                            array_push($myweight[$datum->attemptstepid][$rowid], $colid);
                                        }
                                    }
                                }
                                $mrow[$datum->attemptstepid] = $DB->get_record('question_matrix_rows',
                                    array('id' => $rowid, 'matrixid' => $qmatrix->id));
                                $qtext[$datum->attemptstepid][$rowid] = $mrow[$datum->attemptstepid]->shorttext;
                                $mcol = $DB->get_record('question_matrix_cols',
                                    array('id' => $colid, 'matrixid' => $qmatrix->id));
                                $mans[$datum->attemptstepid][$rowid][$colid] = $mcol->shorttext;
                                $parms = array('rowid' => $rowid, 'colid' => $colid);
                            }
                        } else {
                            // For matrix questions with radio buttons, the key will be cell(\d+).
                            // This gives the row. The answer gives the column for the answer.
                            if (preg_match('/cell(\d+)/', $key, $matches)) {
                                $rowid = $matches[1];
                                $colid = $respon;
                                $mrow = $DB->get_record('question_matrix_rows',
                                    array('id' => $rowid, 'matrixid' => $qmatrix->id));
                                $qtext = $mrow->shorttext;
                                $mcol = $DB->get_record('question_matrix_cols',
                                    array('id' => $colid, 'matrixid' => $qmatrix->id));
                                $mans[$datum->attemptstepid] = $mcol->shorttext;
                                $matrixresponse[$datum->attemptstepid][] = $qtext.':&nbsp;'.$mans[$datum->attemptstepid];
                            }
                        }
                    }
                    if ($qmatrix->multiple == 1) {
                        foreach ($myrow as $rowkey => $value) {
                            if (strlen($qtext[$datum->attemptstepid][$rowkey]) > 0) {
                                $matrixresponse[$datum->attemptstepid][$rowkey] = $qtext[$datum->attemptstepid][$rowkey].":&nbsp;".
                                join(' & ', $mans[$datum->attemptstepid][$rowkey]);
                            }
                        }
                    }
                    $stanswers[$usrid][$datum->questionid] = join(';&nbsp;', $matrixresponse[$datum->attemptstepid]);
                    $stfraction[$usrid][$datum->questionid] = 0.0001;
                } else if ((count($myresponse) > 0) && ($multisingle == 1)) {
                    $clozeresponse = array();// An array for the Close responses.
                    $clozegrade = 0;
                    $multimresponse = array();
                    $multimgrade = 0;
                    foreach ($myresponse as $key => $respon) {
                        // For cloze questions the key will be sub(\d+)_answer.
                        // I need to take the answer that follows part (\d+):(*)?;.
                        if (preg_match('/sub(\d+)\_answer/', $key, $matches)) {
                            $clozequestionid = $datum->questionid;
                            // Finding the number of parts.
                            $numclozeparts = $DB->count_records('question', array('parent' => $clozequestionid));
                            $myres = array();
                            $myres[$key] = $respon;
                            $newres = $mydm->get_fraction($datum->slot, $myres);
                            $clozegrade = $clozegrade + $newres[1];
                            $onemore = $numclozeparts + 1;
                            $tempans = $newres[0]."; part $onemore";
                            $index = $matches[1];
                            $nextindex = $index + 1;
                            $tempcorrect = 'part '.$matches[1].': ';
                            if (preg_match("/$tempcorrect(.*); part $nextindex/", $tempans, $ansmatch)) {
                                $clozeresponse[$matches[1]] = $ansmatch[1];
                            }
                        }
                        // For matrix questions the key will be cell(\d+).
                    }
                    $response = array();
                    if (isset($myresponse['attachments'])) {
                        // Get the linked icon appropriate for this attempt.
                        unset($myresponse['attachments']);
                        if (!isset($stanswers[$usrid][$datum->questionid])) {
                            $stanswers[$usrid][$datum->questionid] = '';// To make sure stanswers is set.
                        }
                        $stlink[$usrid][$datum->questionid] = $mydm->attachment_link(1);
                    } else {
                        $stlink[$usrid][$datum->questionid] = ' ';
                    }
                    if (isset($myresponse['answer'])) {
                        $response = $mydm->get_fraction($datum->slot, $myresponse);
                    }
                    if (count($clozeresponse) > 0) {
                        $stanswers[$usrid][$datum->questionid] = $clozeresponse;
                        $stfraction[$usrid][$datum->questionid] = $clozegrade;
                    } else {
                        if (isset($response[0])) {
                            $stanswers[$usrid][$datum->questionid] = $response[0];
                        }
                    }
                    if (isset($response[1])) {
                        $stfraction[$usrid][$datum->questionid] = $response[1];
                        if ($response[1] == 'NA') {// Make code and tags ineffective.
                            $stanswers[$usrid][$datum->questionid] = $myresponse['answer'];
                        }
                    }
                }
            }
        }

        if ($question->qtype == 'match') {
            foreach ($matchanswer as $qtemptid => $matanswer) {
                $stanswers[$usrid][$datum->questionid] = $matanswer;
                $stfraction[$usrid][$datum->questionid] = $matchgrade[$qtemptid] / $stemcount[$datum->questionid];
            }
        }
    }
    $order = array(); // An array for keeping track of the order of choices for each quiz attemt of each question.
    if ( count($multidata) > 0) {// Here all questions are qtype = multichoice.
        foreach ($multidata as $mdkey => $multidatum) {
            $questionid = $multidatum->questionid;
            $usrid = $multidatum->userid;
            if (!(isset($stfraction[$usrid][$questionid]))) {
                $stanswers[$usrid][$questionid] = ' ';
                $stfraction[$usrid][$questionid] = .001;
            }

            if ($multidatum->name == '_order') {
                $order[$usrid][$questionid] = $multidatum->value;
            }
            $myorder = explode(',', $order[$usrid][$questionid]);
            if ($multidatum->name == 'answer') {// Multichice with only one answer.
                $chosen = $myorder[$multidatum->value];
                $ans = $DB->get_record('question_answers', array('id' => $chosen));
                $anstext = preg_replace('/<p.+?>/', '', $ans->answer);
                $anstext = preg_replace("/<\/p>/", '', $anstext);
                $stanswers[$usrid][$questionid] = $anstext;
                $stfraction[$usrid][$questionid] = $ans->fraction;
            }

            if (preg_match('/^choice(\d+)/', $multidatum->name, $matches)) {// A multichoice with several answers (checkboxes).
                if (!(isset($start[$multidatum->attemptstepid]))) {
                    $multians[$multidatum->attemptstepid] = array();
                    $stfraction[$usrid][$questionid] = .001;
                    $start[$multidatum->attemptstepid] = 1;
                    $yes = array();
                }
                if (!(isset($questionanswers[$questionid]))) {
                    $qans = $DB->get_records('question_answers', array('question' => $questionid));
                    $qarray = array();
                    foreach ($qans as $qan) {
                        $qarray[$qan->id] = $qan;
                    }
                    $questionanswers[$questionid] = $qarray;
                }
                if ($multidatum->value) {
                    $myes = $questionanswers[$questionid][$myorder[$matches[1]]];
                    $anstext = preg_replace('/<p.+?>/', '', $myes->answer);
                    $yes[] = preg_replace("/<\/p>/", '', $anstext);
                    $stfraction[$usrid][$questionid] = $stfraction[$usrid][$questionid] + $myes->fraction;
                }
                $stanswers[$usrid][$questionid] = join('; ', $yes);
                $stfraction[$usrid][$questionid] = $stfraction[$usrid][$questionid] + 0.0001;
            }
        }
    }
    $returnvalues = array($stanswers, $stfraction, $stlink);
    return $returnvalues;
}
/**
 * Return the student answers and fractions for multichoice questions with more than one choice.
 *
 * @param Obj $qattempts The question attempts object for this questionusageid.
 * @param int $usrid The user id for the student doing the question attempt.
 * @return string The HTML string to display the question, including images.
 */
function mmultichoice($qattempts, $usrid) {
    global $DB;
    $multichoiceresponse = '';
    $mgrade = 0;
    $multiorder = '';
    // Possibly multichoice with multiple answers.
    foreach ($qattempts as $qattempt) {
        $myresponse = array();
        $qattemptsteps = $DB->get_records('question_attempt_steps', array('questionattemptid' => $qattempt->id));
        foreach ($qattemptsteps as $qattemptstep) {
            if (($qattemptstep->state == 'complete') || ($qattemptstep->state == 'invalid')
                || ($qattemptstep->state == 'todo')) {
                $answers = $DB->get_records('question_attempt_step_data', array('attemptstepid' => $qattemptstep->id));
                $mchoice = array();
                foreach ($answers as $key => $answer) {
                    $myresponse[$answer->name] = $answer->value;
                    if ($answer->name == '_order') {
                        $multiorder = $answer->value;
                    } else if (($answer->value > 0) && preg_match('/^choice(\d+)/', $answer->name, $matches)) {
                        $mchoice[] = $matches[1];
                    }
                }
                $myorder = explode(",", $multiorder);
                foreach ($mchoice as $mchosen) {
                    $mychoice = $myorder[$mchosen];// One of the chosen question answers.
                    $myanswer = $DB->get_record('question_answers', array('id' => $mychoice));
                    $multichoiceresponse .= $myanswer->answer;
                    $mgrade = $mgrade + $myanswer->fraction;
                }
                $multresponse = preg_replace('/\<\/p\>\<p\>/', "; ", $multichoiceresponse);
                $multresponse = preg_replace('/^\<p\>/', "", $multresponse);
                $multresponse = preg_replace('/\<\/p\>$/', "", $multresponse);
                $stanswers[$usrid][$qattempt->questionid] = $multresponse;
                $stfraction[$usrid][$qattempt->questionid] = $mgrade;
            }
        }
    }
    $result = array($stanswers[$usrid][$qattempt->questionid], $stfraction[$usrid][$qattempt->questionid]);
    return $result;
}
/**
 * Return the text (with images) for one of the questions for a quiz.
 *
 * @param int $cmid The course module id for the quiz.
 * @param int $id The question id.
 * @return array The answers submitted, indexed by userid and questionid, and the corresponding fraction.
 */
function liveviewgrid_display_question($cmid, $id) {
    global $DB, $CFG, $USER;
    $questiontext = "There is some error in obtaining the question.";
    // Most of this code comes from /question/preview.php.
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot. '/question/previewlib.php');

    // Get and validate question id.
    $question = question_bank::load_question($id);
    $cm = get_coursemodule_from_id(false, $cmid);
    $context = context_module::instance($cmid);
    $quba = question_engine::make_questions_usage_by_activity(
            'core_question_preview', context_user::instance($USER->id));
    $options = new question_preview_options($question);
    $options->load_user_defaults();
    $options->set_from_request();
    $quba->set_preferred_behaviour($options->behaviour);
    $slot = $quba->add_question($question, $options->maxmark);
    $quba->start_question($slot, $options->variant);

    $transaction = $DB->start_delegated_transaction();
    question_engine::save_questions_usage_by_activity($quba);
    $transaction->allow_commit();

    if ($question->length) {
        $displaynumber = '1';
    } else {
        $displaynumber = 'i';
    }
    $myquestion = $quba->render_question($slot, $options, $displaynumber);
    if (preg_match("/\<div class\=\"qtext\"\>(.+?)\<\/div\>/m", $myquestion, $matches)) {
        if (!(is_object($matches[1]))) {
            $questiontext = $matches[1];
        }
    } else {
        // Get question text the old way.
        $questionobj = $DB->get_record('question', array('id' => $id));
        $qtext1 = preg_replace('/^<p>/', '', $questionobj->questiontext);
        $qtext2 = preg_replace('/(<br>)*<\/p>$/', '<br />', $qtext1);
        $questiontext = $qtext2;
    }

    return $questiontext;
}

/**
 * Return the row text, column labels, grademethod, and correct answers for matrix questions.
 *
 * @param int $questionid The id for the question.
 * @return array of rowtext, collabel, grademethod, and goodans for the matriz question.
 */
function goodans($questionid) {
    global $DB;
    // Get the column labels, row text, and correct answers.
    $matrixquestion = $DB->get_record('question_matrix', array('questionid' => $questionid));
    $matrixid = $matrixquestion->id;
    $grademethod = $matrixquestion->grademethod;
    $rowtext = array();// An array with the text for each row, indexed by row id.
    $collabel = array();// An array with the label for each column, indexed by column id.
    $rtexts = $DB->get_records('question_matrix_rows', array('matrixid' => $matrixid));
    foreach ($rtexts as $textkey => $rtext) {
        $rowtext[$rtext->id] = $rtext->shorttext;
    }
    $clabels = $DB->get_records('question_matrix_cols', array('matrixid' => $matrixid));
    foreach ($clabels as $labelkey => $clabel) {
        $collabel[$clabel->id] = $clabel->shorttext;
    }
    $goodans = array();// An array of correct choices for a given row, indexed by rodid.
    foreach ($rowtext as $rkey => $rvalue) {
        $rans = array();// An array of good answers from this row.
        foreach ($collabel as $ckey => $cvalue) {
            if ($DB->record_exists('question_matrix_weights', array('rowid' => $rkey, 'colid' => $ckey))) {
                $rans[$ckey] = $cvalue;
            }
        }
        $goodans[$rkey] = $rvalue.':&nbsp;'.implode(' & ', $rans);
    }
    $return = array($rowtext, $collabel, $goodans, $grademethod);
    return $return;
}

/**
 * Function to get the questionids as the keys to the $slots array so we know all the questions in the quiz.
 * @param int $quizid The id for this quiz.
 * @param int $quizcontextid The id of the context for this quiz.
 * @return array $slots The slot values (from the quiz_slots table) indexed by questionids.
 */
function liveviewslots($quizid, $quizcontextid) {
    global $DB;
    $slots = array();
    $slotsvalue = array();
    $myslots = $DB->get_records('quiz_slots', array('quizid' => $quizid));
    $singleqid = optional_param('singleqid', 0, PARAM_INT);
    foreach ($myslots as $key => $value) {
        $slotsvalue[$key] = $value->slot;
    }
    $qreferences = $DB->get_records('question_references',
        array('component' => 'mod_quiz', 'usingcontextid' => $quizcontextid, 'questionarea' => 'slot'));
    foreach ($qreferences as $qreference) {
        $slotid = $qreference->itemid;
        $questionbankentryid = $qreference->questionbankentryid;
        $questionversions = $DB->get_records('question_versions', array('questionbankentryid' => $questionbankentryid));
        foreach ($questionversions as $questionversion) {
            $questionid = $questionversion->questionid;
        }
        $slots[$questionid] = $slotsvalue[$slotid];
    }
    return $slots;
}

/**
 * Function to display the table for the student responses
 * @param array $hidden The various option values.
 * @param int $showresponses Should the student responses be shown (=1).
 * @param int $quizid The id for this quiz.
 * @param int $quizcontextid The id for the context for this quiz.
 */
function liveviewgrid_display_table($hidden, $showresponses, $quizid, $quizcontextid) {
    global $DB, $USER,$CFG;
    // Getting and preparing to sorting users.
    // The first and last name are in the initials array.
    $hidden = liveviewgrid_update_hidden($course);
    foreach ($hidden as $hkey => $hvalue) {
        $$hkey = $hvalue;
    }
    $initials = array();
    $slots = array();
    $question = array();
    $slots = liveviewslots($quizid, $quizcontextid);
    $question = liveviewquestion($slots, $singleqid);
    $sofar = liveview_who_sofar_gridview($quizid);
    if (count($sofar) > 0) {
        foreach ($sofar as $unuser) {
            // If only a group is desired, make sure this student is in the group.
            if ($group) {
                if ($DB->get_record('groups_members', array('groupid' => $group, 'userid' => $unuser))) {
                    $getresponse = true;
                } else {
                    $getresponse = false;
                }
            } else {
                $getresponse = true;
            }
            if ($getresponse) {
                $usr = $DB->get_record('user', array('id' => $unuser));
                if ($order) {
                    $initials[$unuser] = $usr->firstname.'&nbsp;'.$usr->lastname;
                } else {
                    $initials[$unuser] = $usr->lastname.',&nbsp;'.$usr->firstname;
                }
            }
        }
    }
    if ($compact) {
        $trun = 1;
        $dotdot = '';
        // Truncate responses to 4 if compact is desired, else 40 or 200.
    } else {
        $trun = 50;
        $dotdot = '....';
    }
    // This is needed to get the column lined up correctly.
    echo "\n<div id=\"container\" style=\"margin-left:1px;margin-top:1px;background:white;\">";
    echo "\n<table border=\"1\" width=\"100%\" id='timemodified' class='lrtable' name=$qmaxtime>\n";
    echo "<thead><tr>";
    if ($shownames) {
        $activestyle = "style='background-size: 20% 100%;
            background-image: linear-gradient(to right, rgba(170, 225, 170, 1) 0%, rgba(230, 255, 230, 1) 100%);
            background-repeat: repeat;'";
        echo "<th class=\"first-col\">".get_string('name', 'quiz_liveviewgrid')."</th>";
    }
    if ($showlesson) {
        if ($lessonid) {
            echo "<td>".$lesson->name."</td>";
        } else {
            echo "<td>".get_string('nolesson', 'quiz_liveviewgrid')."</td>";
        }
    }
    if ($status) {
        echo "<td>".get_string('progress', 'quiz_liveviewgrid')."</td>";
    }
    // The array for storing the all the texts for tootips.
    $tooltiptext = array();
    $geturl = $CFG->wwwroot.'/mod/quiz/report/liveviewgrid/report.php';
    $togglekey = '';
    foreach ($slots as $key => $slotvalue) {
        if (isset($question['name'][$key])) {
            $hidden['singleqid'] = $key;
            $safequestionname = trim(strip_tags($question['name'][$key]));
            $buttontext = trim($safequestionname);
            $myquestiontext = preg_replace("/[\r\n]+/", '<br />', $question['questiontext'][$key]);
            if (preg_match('/src=\"@@PLUGINFILE@@/', $myquestiontext, $matches)) {
                $quiz = $DB->get_record('quiz', array('id' => $quizid));
                $qslot = $slots[$key];
                $myquestiontext = changepic_url($myquestiontext, $key, $quiz->course, $qslot, $USER->id);
            }
            $ttiptext = get_string('clicksingleq', 'quiz_liveviewgrid').$safequestionname.'<br /><br />'.$myquestiontext;
            // Get rid of any <script> tags that may mess things up.
            $ttiptext = preg_replace("/\<script.*\<\/script\>/m", '', $ttiptext);
            $tooltiptext[] .= "\n    linkqtext_".$key.": '".addslashes($ttiptext)."'";
            $info = '';
            echo "<td>";
            $linkid = "linkqtext_$key";
            if (strlen($buttontext) > $trun) {
                preg_match_all('/./u', $buttontext, $matches);
                $ntrun = 0;
                $truncated = '';
                foreach ($matches[0] as $m) {
                    if ($ntrun < $trun) {
                        $truncated .= $m;
                    }
                    $ntrun++;
                }
                $buttontext = $truncated;
            }
            echo liveview_question_button($buttontext, $hidden, $linkid);
            echo "</td>";
        } else {
            echo "<td></td>";
        }
        if ($question['qtype'][$key] == 'matrix') {
            // Put in correct row answers for each matrix question.
            $goodans[$key] = array();// The good answer for each row, indexed by row. There each question has unique rowids.
            list($rowtext[$key], $collabel[$key], $goodans[$key], $grademethod[$key]) = goodans($key);
        }
    }
    echo "</tr>\n</thead>\n";
    $hidden['singleqid'] = $singleqid;
    if ($showresponses) {
        // Getting and preparing to sorting users.
        // The first and last name are in the initials array.
        if (count($sofar) > 0) {
            list($stanswers, $stfraction, $stlink) = liveviewgrid_get_answers($quizid);
            foreach ($sofar as $unuser) {
                // If only a group is desired, make sure this student is in the group.
                if ($group) {
                    if ($DB->get_record('groups_members', array('groupid' => $group, 'userid' => $unuser))) {
                        $getresponse = true;
                    } else {
                        $getresponse = false;
                    }
                } else {
                    $getresponse = true;
                }
                if ($getresponse) {
                    $usr = $DB->get_record('user', array('id' => $unuser));
                    if ($order) {
                        $initials[$unuser] = $usr->firstname.'&nbsp;'.$usr->lastname;
                    } else {
                        $initials[$unuser] = $usr->lastname.',&nbsp;'.$usr->firstname;
                    }
                }
            }
            if (count($initials)) {
                asort($initials);
                foreach ($initials as $newkey => $initial) {
                    $users[] = $newkey;
                }
            }

        }
            // Javascript and css for tooltips.
            echo "\n<script type=\"text/javascript\">";
            require_once("dw_tooltip_c.php");
            echo "\n</script>";

            echo "\n<style type=\"text/css\">";
            echo "\ndiv#tipDiv {";
                echo "\nfont-size:16px; line-height:1.2;";
                echo "\ncolor:#000; background-color:#E1E5F1;";
                echo "\nborder:1px solid #667295; padding:4px;";
                echo "\nwidth:320px;";
            echo "\n}";
            echo "\n</style>";
            // Create the table body (after the header).
        if (isset($users)) {
            $now = time();
            $firsttime = $now - $activetime * 60;
            echo "\n<tbody>";
            foreach ($users as $user) {
                // Display the row for the student if it is shownames or singleqid == 0 or there is an answer.
                if (($shownames) || ($singleqid == 0) || isset($stanswers[$user][$singleqid])) {
                    echo "<tr>";
                    if ($shownames) {
                        $bgcolor = '';
                        if ($DB->get_records_sql("SELECT id FROM {user} WHERE lastaccess > $firsttime AND id = $user")) {
                            $bgcolor = $activestyle;
                        }
                        echo "<td  class=\"first-col\" $bgcolor>".liveview_find_student_gridview($user)."</td>\n";
                    }
                    $myrow = '';
                    foreach ($slots as $questionid => $slotvalue) {
                        if (isset($stlink[$user][$questionid])) {
                            $link = $stlink[$user][$questionid];
                        } else {
                            $link = '';
                        }
                        if (($questionid != "") && ($questionid != 0)) {
                            if (isset($stanswers[$user][$questionid])) {
                                if (is_array($stanswers[$user][$questionid]) && (count($stanswers[$user][$questionid] > 1))) {
                                    $answer = '';
                                    foreach ($stanswers[$user][$questionid] as $key => $value) {
                                        $answer .= $key."=".$value."; ";
                                    }
                                } else {
                                    $answer = $stanswers[$user][$questionid];
                                }
                                if ($status) {
                                    $ststatus[$user][$questionid] = 1;// Array to keep track of student progress.
                                }
                            } else {
                                $answer = ' ';
                            }
                        }
                            $style = '<td';
                        if ($evaluate) {
                            if ($question['qtype'][$questionid] == 'matrix') {
                                $grade = 0;
                                if (strlen($stanswers[$user][$questionid]) > 0) {
                                    $myansws = explode(';&nbsp;', $stanswers[$user][$questionid]);
                                    $mwrong = 0;// Keeping track of how many wrong answers there are.
                                    foreach ($myansws as $myansw) {
                                        if ($myanskey = array_search($myansw, $goodans[$questionid])) {
                                            $mdata[$myanskey] ++;
                                            $correct = 1;
                                            $grade ++;
                                        } else {
                                            // Find the row for the incorrect answer.
                                            $myanskey = 0;
                                            $anssplit = explode(':&nbsp;', $myansw);
                                            $myanskey = array_search($anssplit[0], $rowtext[$questionid]);
                                            $mdatax[$myanskey] ++;
                                            $mwrong ++;
                                        }
                                    }
                                    $matrixfr = 1;// The fraction for matrix questions, based on grademethod.
                                    if ($grademethod[$questionid] == 'kprime') {
                                        if ($mwrong > 0) {
                                            $matrixfr = 0.001;
                                        }
                                    } else if ($grademethod[$questionid] == 'kany') {
                                        if ($mwrong > 1) {
                                            $matrixfr = 0.001;
                                        } else if ($mwrong > 0) {
                                            $matrixfr = 0.5;
                                        }
                                    } else {
                                        $matrixfr = $grade / count($rowtext[$questionid]) + .001;
                                    }
                                } else {
                                    $matrixfr = 0;
                                }
                                $stfraction[$user][$questionid] = $matrixfr;
                            }
                            if (isset($stfraction[$user][$questionid]) && (!($stfraction[$user][$questionid] == 'NA'))) {
                                $myfraction = $stfraction[$user][$questionid];
                                if ($rag == 1) {// Colors from image from Moodle.
                                    if ($myfraction < 0.0015) {
                                        $redpart = 244;
                                        $greenpart = 67;
                                        $bluepart = 54;
                                    } else if ($myfraction > .999) {
                                        $redpart = 139;
                                        $greenpart = 195;
                                        $bluepart = 74;
                                    } else {
                                        $redpart = 255;
                                        $greenpart = 152;
                                        $bluepart = 0;
                                    }
                                } else {
                                    // Make .5 match up to Moodle amber even when making them different with gradation.
                                    $greenpart = intval(67 + 212 * $myfraction - 84 * $myfraction * $myfraction);
                                    $redpart = intval(244 + 149 * $myfraction - 254 * $myfraction * $myfraction);
                                    if ($redpart > 255) {
                                        $redpart = 255;
                                    }
                                    $bluepart = intval(54 - 236 * $myfraction + 256 * $myfraction * $myfraction);
                                }
                                $style .= " style='background-color: rgb($redpart, $greenpart, $bluepart)'";
                            }
                        }
                        if ((strlen($answer) < $trun) || ($singleqid > 0)) {
                                $myrow .= $style.">&nbsp;".$answer.$link."</td>";
                        } else {
                            // Making a tooltip out of a long answer. The htmlentities function leaves single quotes unchanged.
                            $answer = preg_replace("/&nbsp;/", ' ', $answer);// Changing &nbsp; back to a space.
                            $safeanswer = htmlentities($answer);
                            $safeanswer1 = preg_replace("/\n/", "<br />", $safeanswer);
                            $tooltiptext[] .= "\n    link".$user.'_'.$questionid.": '".addslashes($safeanswer1).$link."'";
                                $myrow .= $style."><div class=\"showTip link".$user.'_'.$questionid."\">";
                            // Making sure we pick up whole words.
                            preg_match_all('/./u', $answer, $matches);
                            $ntrun = 0;
                            $truncated = '';
                            foreach ($matches[0] as $m) {
                                if ($ntrun < $trun) {
                                    $truncated .= $m;
                                }
                                $ntrun++;
                            }
                            $myrow .= $truncated.$link;
                            $myrow .= " $dotdot</div></td>";
                        }
                    }
                    if ($showlesson) {
                        if ($lessonid > 0) {
                            echo "<td>".$lessonstatus[$user]."</td>";
                        } else {
                            echo "<td>".get_string('nolesson', 'quiz_liveviewgrid')."</td>";
                        }
                    }
                    if ($status) {
                        $percentdone = 100 * count($ststatus[$user]) / count($slots);
                        echo "<td>".number_format($percentdone, 1).'%</td>';
                    }
                    echo $myrow;
                    echo "</tr>\n";
                }
            }
            echo "</tbody>";
        }
        echo "\n</table>";
        echo "\n</div>";
    }
    if (count($tooltiptext) > 0) {
        $tooltiptexts = implode(",", $tooltiptext);
        echo "\n<script>";
        echo 'dw_Tooltip.defaultProps = {';
            echo 'supportTouch: true'; // False by default.
        echo '}';

        echo "\ndw_Tooltip.content_vars = {";
            echo $tooltiptexts;
        echo "\n}";
        echo "\n</script>";
    }
}

/**
 * Function to update the hidden values.
 * @param obj $course The course where this quiz is found.
 * @return array $hidden The corrected hidden values.
 */
function liveviewgrid_update_hidden ($course) {
    global $DB;
    $changeoption = optional_param('changeoption', 0, PARAM_INT);
    $rag = optional_param('rag', 1, PARAM_INT);
    $id = optional_param('id', 0, PARAM_INT);
    $mode = optional_param('mode', '', PARAM_ALPHA);
    $evaluate = optional_param('evaluate', 1, PARAM_INT);
    $showkey = optional_param('showkey', 1, PARAM_INT);
    $order = optional_param('order', 0, PARAM_INT);
    $compact = optional_param('compact', 1, PARAM_INT);
    $group = optional_param('group', 0, PARAM_INT);
    $singleqid = optional_param('singleqid', 0, PARAM_INT);
    $showanswer = optional_param('showanswer', 0, PARAM_INT);
    $shownames = optional_param('shownames', 1, PARAM_INT);
    $status = optional_param('status', 0, PARAM_INT);
    if ($lessons = $DB->get_records('lesson', array('course' => $course->id))) {
        $haslesson = 1;
        $lessonid = optional_param('lessonid', 0, PARAM_INT);
        $showlesson = optional_param('showlesson', 0, PARAM_INT);
    } else {
        $haslesson = 0;
        $lessonid = 0;
        $showlesson = 0;
    }
    $refresht = optional_param('refresht', 3, PARAM_INT);
    //$showautorefresh = optional_param('showautorefresh', 0, PARAM_INT);
    $activetime = optional_param('activetime', 10, PARAM_INT);
    // The array of hidden values is hidden[].
    $hidden = array();
    $hidden['rag'] = $rag;
    $hidden['id'] = $id;
    $hidden['mode'] = $mode;
    $hidden['evaluate'] = $evaluate;
    $hidden['showkey'] = $showkey;
    $hidden['order'] = $order;
    $hidden['compact'] = $compact;
    $hidden['group'] = $group;
    $hidden['singleqid'] = $singleqid;
    $hidden['showanswer'] = $showanswer;
    $hidden['shownames'] = $shownames;
    $hidden['status'] = $status;
    $hidden['haslesson'] = $haslesson;
    $hidden['showlesson'] = $showlesson;
    $hidden['lessonid'] = $lessonid;
    $hidden['refresht'] = $refresht;
    //$hidden['showautorefresh'] = $showautorefresh;
    $hidden['activetime'] = $activetime;
    foreach ($hidden as $hiddenkey => $hiddenvalue) {
        if ((!($hiddenkey == 'id')) && (!($hiddenkey == 'singleqid')) && (!($hiddenkey == 'haslesson'))
            && (!($hiddenkey == 'lessonid')) && (!($hiddenkey == 'group'))) {
            // Don't carry group, id, singleqid, haslesson, or lessonid.
            if ($changeoption) {
                $_SESSION[$hiddenkey] = $hiddenvalue;
            } else {
                if (isset($_SESSION[$hiddenkey])) {
                    $hidden[$hiddenkey] = $_SESSION[$hiddenkey];
                }
            }
        }
    }
    return $hidden;
}


/**
 * Function to display the option form.
 * @param array $hidden The various option values.
 */
function liveviewgrid_display_option_form ($hidden) {
    global $CFG;
    foreach ($hidden as $hkey => $hvalue) {
        $$hkey = $hvalue;
    }
    // Script to hide or display the option form.
    echo "\n<script>";
    echo "\nfunction optionfunction() {";
    echo "\n  var e=document.getElementById(\"option1\");";
    echo "\n  var b=document.getElementById(\"button1\");";
    echo "\n  if(e.style.display == \"none\") { ";
    echo "\n      e.style.display = \"block\";";
    echo "\n        b.innerHTML = \"".get_string('clicktohide', 'quiz_liveviewgrid')."\";";
    echo "\n  } else {";
    echo "\n      e.style.display=\"none\";";
    echo "\n      b.innerHTML = \"".get_string('clicktodisplay', 'quiz_liveviewgrid')."\";";
    echo "\n  }";
    echo "\n}";
    echo "\n</script>  ";
    echo "\n<button id='button1' type='button' class='btn btn-primary'  onclick=\"optionfunction()\">";
    echo get_string('clicktodisplay', 'quiz_liveviewgrid')."</button>";
    echo "\n<div class='myoptions' id='option1' style=\"display:none;\">";
    echo "<form action=\"".$CFG->wwwroot."/mod/quiz/report.php\">";
    echo "<input type='hidden' name='changeoption' value=1>";
    echo "<input type='hidden' name='id' value=$id>";
    echo "<input type='hidden' name='mode' value=$mode>";
    echo "<input type='hidden' name='singleqid' value=$singleqid>";
    echo "<input type='hidden' name='group' value=$group>";
    if ($haslesson) {
        echo "<input type='hidden' name='lessonid' value=$lessonid>";
    }
    $checked = array();
    $notchecked = array();
    foreach ($hidden as $hiddenkey => $hiddenvalue) {
        if ($hiddenvalue) {
            $checked[$hiddenkey] = 'checked';
            $notchecked[$hiddenkey] = '';
        } else {
            $checked[$hiddenkey] = '';
            $notchecked[$hiddenkey] = 'checked';
        }
    }
    $twait = array(1, 2, 3, 6, 200);
    foreach ($twait as $myt) {
        $tindex = 'refresht'.$myt;
        if ($refresht == $myt) {
            $checked[$tindex] = 'checked';
        } else {
            $checked[$tindex] = '';
        }
    }
    $tactive = array(5, 10, 30, 60, 600);
    foreach ($tactive as $mat) {
        $aindex = 'activet'.$mat;
        if ($activetime == $mat) {
            $checked[$aindex] = 'checked';
        } else {
            $checked[$aindex] = '';
        }
    }

    $td = "<td style=\"padding:5px 8px;border:1px solid #CCC;\">";
    echo "\n<table>";
    echo "\n<tr>".$td.get_string('thecolorkey', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='showkey' value=1 ".$checked['showkey']."> ";
    echo get_string('yes', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='showkey' value=0 ".$notchecked['showkey']."> ";
    echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    echo "\n<tr>".$td.get_string('colorindicategrades', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='evaluate' value=1 ".$checked['evaluate']."> ";
    echo get_string('yes', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='evaluate' value=0 ".$notchecked['evaluate']."> ";
    echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    echo "\n<tr>".$td.get_string('showstudentnames', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='shownames' value=1 ".$checked['shownames']."> ";
    echo get_string('yes', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='shownames' value=0 ".$notchecked['shownames']."> ";
    echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    echo "\n<tr>".$td.get_string('studentsnames', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='order' value=1 ".$checked['order']."> ";
    echo get_string('firstname', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='order' value=0 ".$notchecked['order']."> ";
    echo get_string('lastname', 'quiz_liveviewgrid')."</td></tr>";
    echo "\n<tr>".$td.get_string('makecompact', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='compact' value=1 ".$checked['compact']."> ";
    echo get_string('yes', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='compact' value=0 ".$notchecked['compact']."> ";
    echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    if ($singleqid > 0) {
        echo "\n<tr>".$td.get_string('correctanswer', 'quiz_liveviewgrid')."</td>";
        echo $td."<input type='radio' name='showanswer' value=1 ".$checked['showanswer']."> ";
        echo get_string('yes', 'quiz_liveviewgrid')."</td>";
        echo $td."<input type='radio' name='showanswer' value=0 ".$notchecked['showanswer']."> ";
        echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    }
    echo "\n<tr>".$td.get_string('colorindicategrade', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='rag' value=1 ".$checked['rag']."> ";
    echo get_string('yes', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='rag' value=0 ".$notchecked['rag']."> ";
    echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    echo "\n<tr>".$td.get_string('showstatus', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='status' value=1 ".$checked['status']."> ";
    echo get_string('yes', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='status' value=0 ".$notchecked['status']."> ";
    echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    echo "\n<tr>".$td.get_string('checkt', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='refresht' value=1 ".$checked['refresht1'].">10 ";
    echo " <input type='radio' name='refresht' value=2 ".$checked['refresht2'].">20 ";
    echo " <input type='radio' name='refresht' value=3 ".$checked['refresht3'].">30 ";
    echo " <input type='radio' name='refresht' value=6 ".$checked['refresht6'].">60 ";
    echo " <input type='radio' name='refresht' value=200 ".$checked['refresht200'].">".
        get_string('nevert', 'quiz_liveviewgrid')."</td>";
    echo "\n<tr>".$td.get_string('tobeactive', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='activetime' value=5 ".$checked['activet5'].">5";
    echo " <input type='radio' name='activetime' value=10 ".$checked['activet10'].">10";
    echo " <input type='radio' name='activetime' value=30 ".$checked['activet30'].">30";
    echo " <input type='radio' name='activetime' value=60 ".$checked['activet60'].">60";
    echo " <input type='radio' name='activetime' value=600 ".$checked['activet600'].">600";
    echo get_string('minutes', 'quiz_liveviewgrid');
    echo "</td></tr>";
    /*
    echo "\n<tr>".$td.get_string('showautorefresh', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='showautorefresh' value=1 ".$checked['showautorefresh'].">";
    echo get_string('yes', 'quiz_liveviewgrid')."</td>";
    echo $td."<input type='radio' name='showautorefresh' value=0 ".$notchecked['showautorefresh']."> ";
    echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    echo "</td></tr>";
    */
    if ($haslesson) {
        echo "\n<tr>".$td.get_string('showlessonstatus', 'quiz_liveviewgrid')."</td>";
        echo $td."<input type='radio' name='showlesson' value=1 ".$checked['showlesson']."> ";
        echo get_string('yes', 'quiz_liveviewgrid')."</td>";
        echo $td."<input type='radio' name='showlesson' value=0 ".$notchecked['showlesson']."> ";
        echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
    }
    echo "\n</table>";
    $buttontext = get_string('submitoptionchanges', 'quiz_liveviewgrid');
    echo "<br /><input type=\"submit\" value=\"$buttontext\"></form>";
    echo "</div>";
}

/**
 * Function to get the qtype, name, questiontext for each question.
 * @param array $slots and array of slot ids indexed by question ids.
 * @param int $singleqid The id of the question in the quiz.
 * @return array $question. A doubly indexed array giving qtype, qname, and qtext for the questions.
 */
function liveviewquestion($slots, $singleqid) {
    global $DB;
    $question = array();
    if ($singleqid > 0) {
        $questionid = $singleqid;
        $myquestion = $DB->get_record('question', array('id' => $questionid));
        $question['qtype'][$questionid] = $myquestion->qtype;
        $question['name'][$questionid] = $myquestion->name;
        $question['questiontext'][$questionid] = $myquestion->questiontext;
    } else {
        foreach ($slots as $questionid => $slotvalue) {
            if ($myquestion = $DB->get_record('question', array('id' => $questionid))) {
                $question['qtype'][$questionid] = $myquestion->qtype;
                $question['name'][$questionid] = $myquestion->name;
                $question['questiontext'][$questionid] = $myquestion->questiontext;
            }
        }
    }
    return $question;
}

/**
 * Function to change the urls of image links to the correct url.
 * @param text $qtext2 The text to be changed.
 * @param int $questionid The id of the question where the text is found.
 * @param int $courseid The id course containing this quiz.
 * @param int $slot the id of the slot where this question is found.
 * @param int $userid The id for the user (teacher) using the Live Report module.
 */
function changepic_url($qtext2, $questionid, $courseid, $slot, $userid) {
    global $CFG, $DB;
    $pics = array();
    $pics = explode('@@PLUGINFILE@@/', $qtext2);
    $time = time();
    $ccontext = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $courseid));
    $coursecontextid = $ccontext->id;
    $ucontext = $DB->get_record('context', array('contextlevel' => 30, 'instanceid' => $userid));
    $usercontextid = $ucontext->id;
    $quba = $DB->insert_record('question_usages', array('contextid' => $usercontextid, 'component' => 'core_question_preview',
        'preferredbehaviour' => 'deferredfeedback'));
    $attempt = $DB->insert_record('question_attempts', array('questionusageid' => $quba, 'slot' => $slot,
        'behaviour' => 'deferredfeedback', 'questionid' => $questionid, 'variant' => 1, 'maxmark' => 1.0, 'minfraction' => 0.0,
        'flagged' => 0, 'questionsummary' => 'Hi, world', 'rightanswer' => 'Helloworld', 'timemodified' => $time));
    $astep = $DB->insert_record('question_attempt_steps', array('questionattemptid' => $attempt, 'sequencenumber' => 0,
        'state' => 'todo', 'timecreated' => $time, 'userid' => $userid));
    $replacetext = $CFG->wwwroot."/pluginfile.php/$coursecontextid/question/questiontext/$quba/$slot/$questionid/";
    $qtextgood = implode($replacetext, $pics);
    return $qtextgood;
}
