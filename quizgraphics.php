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
 * Displays the quiz HIstogram or text resposes.
 *
 * An indicaton of # of responses to this question/# of student responding to this quiz instance is printed.
 * After that the histogram or the text responses are printed, depending on the question type.
 * @package   quiz_liveviewgrid
 * @copyright  2018 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
$quizid = optional_param('quizid', 0, PARAM_INT);
$questionid = optional_param('question_id', 0, PARAM_INT);
$showstudents = optional_param('showstudents', 0, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$quiz = $DB->get_record('quiz', array('id' => $quizid));
$course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
require_login($course, true, $cm);
$contextinstance = context_module::instance($cm->id);
echo "<html><head>";
echo "\n<title>".get_string('questionresponses', 'quiz_liveviewgrid')."</title>";
echo "\n</head><body>";
if (!(has_capability('mod/quiz:manage', $contextinstance))) {
    echo "\n<br />".get_string('youmustbeauthorized', 'quiz_liveviewgrid');
    echo "\n</body><html>";
    exit;
}
$groupmode = groups_get_activity_groupmode($cm, $course);
$currentgroup = groups_get_activity_group($cm, true);
$contextmodule = context_module::instance($cm->id);
$showresponses = false;

if ($groupmode == 1 && !has_capability('moodle/site:accessallgroups', $contextmodule)) {
    if ($group == 0) {
        // Teacher cannot see all groups and no group has been selected.
        $showresponses = false;
        echo get_string('pickgroup', 'quiz_liveviewgrid');
        exit;
    } else if ($currentgroup > 0) {
        if ($DB->get_record('groups_members', array('groupid' => $group, 'userid' => $USER->id))) {
            // The teacher is a member of this group.
            $showresponses = true;
        } else {
            // Teacher has picked a group but is not a member of this group.
            $showresponses = false;
            echo get_string('notmember', 'quiz_liveviewgrid');
            exit;
        }
    }
} else {
    $showresponses = true;
}

if (!($showresponses)) {
    echo get_string('notallowedgroup', 'quiz_liveviewgrid');
    echo "\n</body><html>";
    exit;
}
echo get_string('responses', 'quiz_liveviewgrid');
if ($group) {
    $grpname = $DB->get_record('groups', array('id' => $group));
    echo get_string('from', 'quiz_liveviewgrid').$grpname->name;
}

$questionanswerids = array();
$multitype = array('multichoice', 'truefalse', 'calculatedmulti');
if (!($questiontext = $DB->get_record('question', array('id' => $questionid)))) {
    echo "\n<br />".get_string('youmustsubmitquestonid', 'quiz_liveviewgrid');
    echo "\n</body><html>";
    exit;
}
if (in_array($questiontext->qtype, $multitype)) {
    $order = true;
} else {
    $order = false;
}
$qanswerids = array();

// For those questions that have answers, get the possible answers and create the labels for the histogram.
if ($answers = $DB->get_records('question_answers', array('question' => $questionid))) {
    $labels = '';
    $n = 0;
    foreach ($answers as $answer) {
        if ($order) {
            $qanswerids[$n] = $answer->id;// Needed for truefalse questions.
            $labels .= "&x[$n]=".substr(strip_tags($answer->answer), 0, 15);
            $n++;
        }
        if ($questiontext->qtype == 'ddwtos') {
            $qanswertext[$n + 1] = $answer->answer;
            $n++;
        }
    }
}

$stans = array();// The string of answers for each student to this question, indexed by the $userid.
$quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
foreach ($quizattempts as $quizattempt) {
    $userid = $quizattempt->userid;
    // Check that groups are not being used or that the student is a member of the group.
    if (($group == 0) || ($DB->get_record('groups_members', array('groupid' => $group, 'userid' => $userid)))) {
        $uniqueid = $quizattempt->uniqueid;
        $questionattempts = $DB->get_records('question_attempts'
            , array('questionusageid' => $uniqueid, 'questionid' => $questionid));
        foreach ($questionattempts as $questionattempt) {
            $attemptid = $questionattempt->id;
            $attemptsteps = $DB->get_records('question_attempt_steps', array('questionattemptid' => $attemptid));
            foreach ($attemptsteps as $attemptstep) {
                // Every time a student submits an answer, this generates a new question_attempt_step.
                // Submitting one answer can generate several rows in the question_attempt_step_data table.
                $stanswer = array();// The array of questionanswerids for this student for multichoice with several answers.
                $attemptstepid = $attemptstep->id;
                $attemptdata = $DB->get_records('question_attempt_step_data',  array('attemptstepid' => $attemptstepid));
                foreach ($attemptdata as $datainfo) {
                    $name = $datainfo->name;
                    $value = $datainfo->value;
                    if ($name == '_order') {
                        // The order step_data should always occur before the answers or choices, except for truefalse.
                        $questionanswerids = explode(',', $value);
                    } else if ($attemptstep->state == 'complete') {
                        if ($name == 'answer') {
                            if ($order) {
                                if ($questiontext->qtype == 'truefalse') {
                                    $truefalseindex = 1 - $value;
                                    $stans[$userid] = $qanswerids[$truefalseindex];
                                } else {
                                    $stans[$userid] = $questionanswerids[$value];
                                }
                            } else {
                                $stans[$userid] = $value;
                            }
                        }
                        if (preg_match('/choice(\d)/', $name, $matches)) {
                            if ($value > 0) {
                                $stanswer[] = $questionanswerids[$matches[1]];
                            }
                        }
                        if (preg_match('/p(\d)/', $name, $matches)) {
                            if ($value > 0) {
                                $stanswer[] = $qanswertext[$value];
                            }
                        }
                    }
                }
                if (count($stanswer)) {
                    $stans[$userid] = implode(',', $stanswer);
                }
            }
        }
    }
}
if ($order) {
    $myx = array();
    foreach ($qanswerids as $qanswerid) {
        $myx[$qanswerid] = 0;
    }
    foreach ($stans as $key => $value) {
        if (strlen($value) > 0) {
            $values = explode(',', $value);
            foreach ($values as $qansid) {
                if (isset($myx[$qansid])) {
                    $myx[$qansid] ++;
                } else {
                    echo "\n<br />".get_string('somethingiswrongwithanswerid', 'quiz_liveviewgrid')." $qansid";
                }
            }
        }
    }

    $graphinfo = "?data=".implode(",", $myx).$labels."&total=10";
    echo "\n<br />".get_string('questionis', 'quiz_liveviewgrid').$questiontext->questiontext;
    $graphicurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/graph.php";
    echo "\n<br /><img src=\"".$graphicurl.$graphinfo."&cmid=".$cm->id."\"></img>";
} else {
    echo "\n<br />";
    $quizgraphicsurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/quizgraphics.php";
    if ($showstudents) {
        echo "<a href='".$quizgraphicsurl."?question_id=$questionid&quizid=$quizid&showstudents=0&group=$group'>";
        echo get_string('hidenames', 'quiz_liveviewgrid')."</a>";
    } else {
        echo "<a href='".$quizgraphicsurl."?question_id=$questionid&quizid=$quizid&showstudents=1&group=$group'>";
        echo get_string('shownames', 'quiz_liveviewgrid')."</a>";
    }
    echo '<table border=1><tr>';
    if ($showstudents) {
        echo "<td>".get_string('firstname', 'quiz_liveviewgrid')."</td><td>".get_string('lastname', 'quiz_liveviewgrid')."</td>";
    }
    echo "<td>".get_string('questionis', 'quiz_liveviewgrid').$questiontext->questiontext."</td></tr>";
    foreach ($stans as $usr => $textanswer) {
        echo "\n<tr><td>";
        if ($showstudents) {
            $user = $DB->get_record('user', array('id' => $usr));
            echo $user->firstname."</td><td>".$user->lastname."</td><td>";
        }
        echo strip_tags($textanswer);
        echo "</td></tr>";
    }
    echo "\n</table>";
}
echo "\n</body><html>";
