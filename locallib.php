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

defined('MOODLE_INTERNAL') || die();

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
    $mytext = "\n<form action=\"".$CFG->wwwroot."/mod/quiz/report.php\" target=\"_top\">";
    foreach ($hidden as $key => $value) {
        $mytext .= "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
    }
    $mytext .= "<div class=\"showTip $linkid\">";
    $mytext .= "<input type=\"submit\" value=\"".htmlentities($buttontext)."\"></form>";
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
 *
 * @param int $quizid The id for the quiz.
 * @param string $geturl The url for the form when submit is clicked.
 * @param array $hidden The array of keys and values for the hidden inputs in the form.
 */
function liveviewgrid_question_dropdownmenu($quizid, $geturl, $hidden) {
    global $DB, $USER;
    echo "\n<table border=0><tr><td>";
    echo get_string('whichquestion', 'quiz_liveviewgrid')."</td>";
    $slots = $DB->get_records('quiz_slots', array('quizid' => $quizid));
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
    foreach ($slots as $slot) {
        $question = $DB->get_record('question', array('id' => $slot->questionid));
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

/**
 * A function to return the most recent response of all students to the questions in a quiz and the grade for the answers.
 *
 * @param int $quizid The id for the quiz.
 * @return array $returnvalues. $returnvalues[0] = $stanswers[$stid][$qid], $returnvalues[1] = $stfraction[$stid][$qid].
 **/
function liveviewgrid_get_answers($quizid) {
    global $DB;
    $quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
    // These arrays are the 'answr' or 'fraction' or 'link' (for attachments) indexed by userid and questionid.
    $stanswers = array();
    $stfraction = array();
    $stlink = array();
    foreach ($quizattempts as $key => $quizattempt) {
        $usrid = $quizattempt->userid;
        $qubaid = $quizattempt->uniqueid;
        $mydm = new quiz_liveviewgrid_fraction($qubaid);
        $qattempts = $DB->get_records('question_attempts', array('questionusageid' => $qubaid));
        foreach ($qattempts as $qattempt) {
            $myresponse = array();
            $qattemptsteps = $DB->get_records('question_attempt_steps', array('questionattemptid' => $qattempt->id));
            foreach ($qattemptsteps as $qattemptstep) {
                if (($qattemptstep->state == 'complete') || ($qattemptstep->state == 'invalid')
                    || ($qattemptstep->state == 'todo')) {
                    // Handling Cloze questions, 'invalid' and immediatefeedback, 'todo'.
                    $answers = $DB->get_records('question_attempt_step_data', array('attemptstepid' => $qattemptstep->id));
                    foreach ($answers as $answer) {
                        $myresponse[$answer->name] = $answer->value;
                    }
                    $question = $DB->get_record('question', array('id' => $qattempt->questionid));
                    if ($question->qtype == 'matrix') {// Check to see if attachments can be sent in matrix questions.
                        $matrixresponse = array();
                        $mgrade = 0;
                        $mweight = 0.00001;
                        $qmatrix = $DB->get_record('question_matrix', array('questionid' => $qattempt->questionid));
                        $numrows = $DB->count_records('question_matrix_rows', array('matrixid' => $qmatrix->id));
                        foreach ($myresponse as $key => $respon) {
                            // For matrix questions the key will be cell(\d)*.
                            // This gives the row. The answer gives the column for the answer.
                            if (preg_match('/cell(\d)*/', $key, $matches)) {
                                $rowid = $matches[1];
                                $colid = $respon;
                                $weight = 0;
                                if (($rowid > 0) && ($colid > 0)) {
                                    $parms = array('rowid' => $rowid, 'colid' => $colid);
                                    if ($fract = $DB->get_record('question_matrix_weights', $parms)) {
                                        $weight = $fract->weight;
                                    }
                                }
                                $mrow = $DB->get_record('question_matrix_rows', array('id' => $rowid, 'matrixid' => $qmatrix->id));
                                $qtext = $mrow->shorttext;
                                $mcol = $DB->get_record('question_matrix_cols', array('id' => $colid, 'matrixid' => $qmatrix->id));
                                $mans = $mcol->shorttext;
                                $matrixresponse[] = $qtext."= ".$mans;
                                $parms = array('rowid' => $rowid, 'colid' => $colid);
                                if ($mwgt = $DB->get_record('question_matrix_weights', $parms)) {
                                    $mweight = $mweight + $mwgt->weight;
                                }
                            }
                        }
                        $stanswers[$usrid][$qattempt->questionid] = join('; ', $matrixresponse);
                        $stfraction[$usrid][$qattempt->questionid] = $mweight / $numrows;
                    } else if (count($myresponse) > 0) {
                        $clozeresponse = array();// An array for the Close responses.
                        $clozegrade = 0;
                        foreach ($myresponse as $key => $respon) {
                            // For cloze questions the key will be sub(\d*)_answer.
                            // I need to take the answer that follows part (\d):(*)?;.
                            if (preg_match('/sub(\d)*\_answer/', $key, $matches)) {
                                $clozequestionid = $qattempt->questionid;
                                // Finding the number of parts.
                                $numclozeparts = $DB->count_records('question', array('parent' => $clozequestionid));
                                $myres = array();
                                $myres[$key] = $respon;
                                $newres = $mydm->get_fraction($qattempt->slot, $myres);
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
                            if (!isset($stanswers[$usrid][$qattempt->questionid])) {
                                $stanswers[$usrid][$qattempt->questionid] = '';// To make sure stanswers is set.
                            }
                            $stlink[$usrid][$qattempt->questionid] = $mydm->attachment_link(1);
                        } else {
                            $stlink[$usrid][$qattempt->questionid] = ' ';
                        }
                        if (isset($myresponse['answer'])) {
                            $response = $mydm->get_fraction($qattempt->slot, $myresponse);
                        }
                        if (count($clozeresponse) > 0) {
                            $stanswers[$usrid][$qattempt->questionid] = $clozeresponse;
                            $stfraction[$usrid][$qattempt->questionid] = $clozegrade;
                        } else {
                            if (isset($response[0])) {
                                $stanswers[$usrid][$qattempt->questionid] = $response[0];
                            }
                        }
                        if (isset($response[1])) {
                            $stfraction[$usrid][$qattempt->questionid] = $response[1];
                            if ($response[1] == 'NA') {// Make code and tags ineffective.
                                $stanswers[$usrid][$qattempt->questionid] = $myresponse['answer'];
                            }
                        }
                    }
                }
            }
        }
    }
    $returnvalues = array($stanswers, $stfraction, $stlink);
    return $returnvalues;

}

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
    question_require_capability_on($question, 'use');
    $quba = question_engine::make_questions_usage_by_activity(
            'core_question_preview', context_user::instance($USER->id));
    $options = new question_preview_options($question);
    $options->load_user_defaults();
    $options->set_from_request();
    //$PAGE->set_url(question_preview_url($id, $options->behaviour, $options->maxmark, $options, $options->variant, $context));

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
