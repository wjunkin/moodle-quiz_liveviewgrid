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
 * Creates the quiz histogram graph. It uses the param values and then uses the /lib/graphlib.php script.
 *
 * It takes the values given to it to create the graph.
 * It does not access any information from the Moodle site.
 * @package   quiz_liveviewgrid
 * @copyright  2012 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
defined('MOODLE_INTERNAL') || die();
$questionid = optional_param('questionid', 0, PARAM_INT);
$quizid = optional_param('quizid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$evaluate = optional_param('evaluate', 0, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$shownames = optional_param('shownames', 0, PARAM_INT);
$order = optional_param('order', 0, PARAM_INT);
$questiontext = $DB->get_record('question', array('id' => $questionid));
$cmid = optional_param('cmid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid));
require_login($course, true, $cm);
$context = context_module::instance($cmid);
require_capability('mod/quiz:manage', $context);
$barnames = array(); // An array of the names associated with each choice. Index = choice and value = studentid.
// For those questions that have answers, get the possible answers and create the labels for the histogram.
if ($answers = $DB->get_records('question_answers', array('question' => $questionid))) {
    $labels = '';
    $fraction = '';
    $n = 0;
    foreach ($answers as $answer) {
        $qanswerids[$n] = $answer->id;// Needed for truefalse questions.
        $labels .= "&x[$n]=".substr(strip_tags($answer->answer), 0, 15);
        $myfraction = $answer->fraction;
        if ($evaluate) {
            $fraction .= "&fr[$n]=$myfraction";
        }
        $n++;
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
                            if ($questiontext->qtype == 'truefalse') {
                                $truefalseindex = 1 - $value;
                                $stans[$userid] = $qanswerids[$truefalseindex];
                            } else {
                                $stans[$userid] = $questionanswerids[$value];
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
$myx = array();
foreach ($qanswerids as $qanswerid) {
    $myx[$qanswerid] = 0;
    $barnames[$qanswerid] = '';
}
foreach ($stans as $key => $value) {
    if (strlen($value) > 0) {
        $values = explode(',', $value);
        foreach ($values as $qansid) {
            if (isset($myx[$qansid])) {
                $myx[$qansid] ++;
                $name = $DB->get_record('user', array('id' => $key));
                if ($order == 1) {// Order by first name.
                    $barnames[$qansid] .= $name->firstname.'&nbsp;'.$name->lastname.';;';
                } else {
                    $barnames[$qansid] .= $name->lastname.',&nbsp;'.$name->firstname.';;';
                }
            } else {
                echo "\n<br />".get_string('somethingiswrongwithanswerid', 'quiz_liveviewgrid')." $qansid";
            }
        }
    }
}
$graphinfo = "?data=".implode(",", $myx).$labels.$fraction."&total=10";
$mygraphinfo = "data=".implode(",", $myx).$labels.$fraction."&total=10&cmid=$cmid";
$qanswers = $DB->get_records('question_answers', array('question' => $questionid));
$numofbars = count($qanswers);
$i = 0;
foreach ($qanswers as $qanswer) {
    $mynames[$i] = $barnames[$qanswer->id];
    $choice[$i] = $qanswer->answer;
    $fr[$i] = $qanswer->fraction;
    $i++;
}
if ($numofbars == 0) {
    echo "Something is wrong for question $questionid.";
    exit;
}
echo "<html><head>";
$xwidthpx = intval(648 / $numofbars).'px';
$barwidthpx = intval(486 / $numofbars).'px';

echo "
<style>
.tooltip {
  position: absolute;
  bottom: 40px;
  width: $barwidthpx;
  border: 0;
}

.tooltip .tooltiptext {
  visibility: hidden;
  width: 120px;
  background-color: #555;
  color: #fff;
  text-align: center;
  border-radius: 6px;
  padding: 5px 0;
  position: absolute;
  z-index: 1;
  bottom: 125%;
  left: 50%;
  margin-left: -60px;
  opacity: 0;
  transition: opacity 0.3s;
}

.tooltip .tooltiptext::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border-width: 5px;
  border-style: solid;
  border-color: #555 transparent transparent transparent;
}

.tooltip:hover .tooltiptext {
  visibility: visible;
  opacity: 1;
}
.mytooltip {
  position: absolute;
  bottom: 20;
  width:  $xwidthpx;
  border: 0;
}

.mytooltip .tooltiptext {
  visibility: hidden;
  width: 120px;
  background-color: #555;
  color: #fff;
  text-align: center;
  border-radius: 6px;
  padding: 5px 0;
  position: absolute;
  z-index: 1;
  bottom: 125%;
  left: 50%;
  margin-left: -60px;
  opacity: 0;
  transition: opacity 0.3s;
}

.mytooltip .tooltiptext::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border-width: 5px;
  border-style: solid;
  border-color: #555 transparent transparent transparent;
}

.mytooltip:hover .tooltiptext {
  visibility: visible;
  opacity: 1;
}
";
echo "\n</style>";
echo "\n<body style=\"text-align:center;\">";
$y = array();
$xaxis = '';
for ($i = 0; $i < $numofbars; $i++) {
    $y[$i] = $i + 1;
    $xaxis .= "&x[$i]=".$choice[$i];
}
$data = 'data='.implode(',', $y);
$get = $data.$xaxis.'&total=10&cmid=3';
if ($evaluate == 1) {
    $get .= $fraction;
}
$xoffset = 47;
$xwidth = intval(648 / $numofbars);
$baroffset = intval(81 / $numofbars) + 47;
echo "\n<image src=\"http://localhost/moodle362/mod/quiz/report/liveviewgrid/graph.php?$mygraphinfo\">";
for ($i = 0; $i < $numofbars; $i++) {
    $left = $xoffset + ($i * $xwidth);
    echo "\n<div class=\"mytooltip\" style=\"left: $left\">&nbsp;";
    $tooltiptext = $choice[$i];
    echo "\n<span class=\"tooltiptext\">$tooltiptext</span>";
    echo "\n</div>";
}

/**
 * Function to find the number of students in a course or a group.
 *
 * @param int $courseid The id for the course.
 * @param int $group The id number for the group. (0 is no group selected.)
 * @return int The number of student in the course or group.
 */
function count_members($courseid, $group) {
    global $DB;
    $studentrole = $DB->get_record('role', array('shortname' => 'student'));
    $context = context_course::instance($courseid);
    $roster = $DB->get_records('role_assignments', array('contextid' => $context->id, 'roleid' => $studentrole->id));
    if ($group > 0) {
        $n = 0;
        foreach ($roster as $members) {
            if ($DB->get_record('groups_members', array('groupid' => $group, 'userid' => $members->userid))) {
                $n++;
            }
        }
        $count = $n;
    } else {
        $count = count($roster);
    }
    return $count;
}
for ($i = 0; $i < $numofbars; $i++) {
    // Create an array of the names so they can be ordered.
    $namesstring = '';
    $namesarray = explode(';;', $mynames[$i]);
    sort($namesarray);
    if (count($namesarray) > 1) {
        $members = count_members($courseid, $group);
        $val = ((count($namesarray) - 1) / $members) * 100;
        $percent = round($val, 1);
        $namesstring = $percent."%\n<br />";
        if ($shownames) {
            $namesstring .= implode(" ", $namesarray);
        }
    }
    $barleft = $baroffset + ($i * $xwidth);
    echo "\n<div class=\"tooltip\" style=\"left: $barleft\">".$namesstring;
    echo "</div>";
}

echo "\n</image>";
echo "\n</body>";
echo "\n</html>";
