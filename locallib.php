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
    $mytext = "\n<form action=\"".$CFG->wwwroot."/mod/quiz/report.php\" target=\"_parent\">";
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
function liveviewgrid_question_dropdownmenu($quizid, $geturl, $hidden,$quizcontextid) {
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

/**
 * A function to return the most recent response of all students to the questions in a quiz and the grade for the answers.
 *
 * @param int $quizid The id for the quiz.
 * @return array $returnvalues. $returnvalues[0] = $stanswers[$stid][$qid], $returnvalues[1] = $stfraction[$stid][$qid].
 **/
function liveviewgrid_get_answers($quizid) {
    global $DB;
	if ($singleqid > 0) {
		$sqldata = "SELECT qasd.*, qa.questionid, qza.userid, qza.uniqueid, qas.state, qa.slot, qa.questionsummary
		FROM {question_attempt_step_data} qasd 
		JOIN {question_attempt_steps} qas ON qasd.attemptstepid = qas.id
		JOIN {question_attempts} qa ON qas.questionattemptid = qa.id
		JOIN {quiz_attempts} qza ON qa.questionusageid = qza.uniqueid
		WHERE qza.quiz = $quizid AND qa.questionid = $singleqid";
	} else {
		$sqldata = "SELECT qasd.*, qa.questionid, qza.userid, qza.uniqueid, qas.state, qa.slot, qa.questionsummary
		FROM {question_attempt_step_data} qasd 
		JOIN {question_attempt_steps} qas ON qasd.attemptstepid = qas.id
		JOIN {question_attempts} qa ON qas.questionattemptid = qa.id
		JOIN {quiz_attempts} qza ON qa.questionusageid = qza.uniqueid
		WHERE qza.quiz = $quizid";
	}
	$params = array();
	$data = $DB->get_records_sql($sqldata, $params);

    // These arrays are the 'answr' or 'fraction' or 'link' (for attachments) indexed by userid and questionid.
    $stanswers = array();
    $stfraction = array();
    $stlink = array();
    $singleqid = optional_param('singleqid', 0, PARAM_INT);
	// The array for $data to multichoice questions with more than one answer (checkboxes).
	$datumm = array();
    foreach ($data as $key => $datum) {
        $usrid = $datum->userid;
        $qubaid = $datum->uniqueid;
        $mydm = new quiz_liveviewgrid_fraction($qubaid);
		$question = $DB->get_record('question', array('id' => $datum->questionid));
		if ($question->qtype == 'multichoice') {
			$multidata[$datum->id] = $datum;
			// I will deal with multichoice later.
		} elseif (($question->qtype == 'essay') || ($question->qtype == 'shortanswer')){
			if ($datum->name == 'answer') {
				$stanswers[$usrid][$datum->questionid] = $datum->value;
			}
		} elseif ($question->qtype == 'truefalse') {
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
					if (!(isset($matrixrowanswers[$qmatrix->id]))) {//This is the first time for this matrix.
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
								$matrixresponse[$datum->attemptstepid][$rowkey] = $qtext[$datum->attemptstepid][$rowkey].":&nbsp;".join(' & ', $mans[$datum->attemptstepid][$rowkey]);
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
	if (count($multidata) > 0) {// Here all questions are qtype = multichoice.
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
			
			if (preg_match('/^choice(\d+)/', $multidatum->name, $matches)) {// A multichoice with several possible answers (checkboxes).
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
	$qreferences = $DB->get_records('question_references', array('component' => 'mod_quiz', 'usingcontextid' => $quizcontextid, 'questionarea' => 'slot'));
	foreach ($qreferences as $qreference) {
		$slotid = $qreference -> itemid;
		$questionbankentryid = $qreference-> questionbankentryid;
		$questionversions = $DB->get_records('question_versions', array('id' => $questionbankentryid));
		foreach ($questionversions as $questionversion) {
			$questionid = $questionversion->questionid;
		}
		$slots[$questionid] = $slotsvalue[$slotid];
	}
	return $slots;
}

