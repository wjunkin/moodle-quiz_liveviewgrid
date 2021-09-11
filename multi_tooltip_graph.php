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
$rag = optional_param('rag', 0, PARAM_INT);
$questionqid = optional_param('questionqid', 0, PARAM_INT);
$quizid = optional_param('quizid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$evaluate = optional_param('evaluate', 0, PARAM_INT);
$norefresh = optional_param('norefresh', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$shownames = optional_param('shownames', 0, PARAM_INT);
$order = optional_param('order', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$cmid = $id;
$cm = get_coursemodule_from_id('quiz', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid));
require_login($course, true, $cm);
$context = context_module::instance($cmid);
require_capability('mod/quiz:manage', $context);
$barnames = array(); // An array of the names associated with each choice. Index = choice and value = studentid.
// Usually only want answers after the question was sent, so need to find out when the question was sent.
if ($questionqid) {
    $timesent = 0;
    $questionid = $questionqid;
} else {
    if ($groupid > 0) {
        $question = $DB->get_record('quiz_current_questions', array('quiz_id' => $quizid, 'groupid' => $groupid));
        $users = explode(',', $question->groupmembers);
    } else {
        $question = $DB->get_record('quiz_current_questions', array('quiz_id' => $quizid));
    }
    $questionid = $question->question_id;
    $timesent = $question->timemodified;
}

$multitype = array('multichoice', 'truefalse', 'calculatedmulti');
if (!($questiontext = $DB->get_record('question', array('id' => $questionid)))) {
    echo "\n<br />You must submit a valid questionid";
    exit;
}
if (in_array($questiontext->qtype, $multitype)) {
    $histogram = true;
} else {
    $histogram = false;
}

if ($histogram) {
    // For those questions that have answers, get the possible answers and create the labels for the histogram.
    $labels = '';
    $qanswerids = array();
    $fraction = '';
    if ($answers = $DB->get_records('question_answers', array('question' => $questionid))) {
        $n = 0;
        foreach ($answers as $answer) {
            $qanswerids[$n] = $answer->id;// Needed for truefalse questions.
            $labels .= "&x[$n]=".substr(strip_tags($answer->answer), 0, 15);
            $myfraction = $answer->fraction;
            if ($evaluate) {
                if ($rag && ($myfraction <> 0) && ($myfraction <> 1)) {
                    $myfraction = 0.5;
                }
                $fraction .= "&fr[$n]=$myfraction";
            }
            $n++;
        }
    }
}
$stans = array();// The string of answers for each student to this question, indexed by the $userid.
$quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
foreach ($quizattempts as $quizattempt) {
    if ($quizattempt->timemodified > $timesent) {// Only want responses after time sent.
        $userid = $quizattempt->userid;
        // Check that groups are not being used or that the student is a member of the group.
        if (($groupid == 0) || ($DB->get_record('groups_members', array('groupid' => $groupid, 'userid' => $userid)))) {
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
                    if ($histogram) {
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
                    } else {
                        foreach ($attemptdata as $datainfo) {
                            $name = $datainfo->name;
                            $value = $datainfo->value;
                            if ($name == 'answer') {
                                $stans[$userid] = $value;
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($histogram) {
    $myx = array();
    if (count($qanswerids) > 0) {
        foreach ($qanswerids as $qanswerid) {
            $myx[$qanswerid] = 0;
            $barnames[$qanswerid] = '';
        }
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
}
echo "<html><head>";
if ($histogram) {
    $barwidthpx = 1;
    if ($numofbars > 0) {
        $barwidthpx = intval(486 / $numofbars).'px';
    }
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
    ";
    echo "\n</style>";
}
echo "\n<body style=\"text-align:center;\">";
if (!($norefresh)) {
    // Put in the warning to refresh the page after 2 hours of checking refresh.
    echo "\n<div id=\"blink1\" class=\"blinkhidden\" style=\"display:none;\">";
    $iframeurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/multi_tooltip_graph.php";
    echo "\n<form action='$iframeurl' method='get'><input type='submit' value='Click to Refresh Data' class=\"blinking\">";
    echo "\n<input type='hidden' name='quizid' value='$quizid'>";
    echo "\n<input type='hidden' name='courseid' value='$courseid'>";
    echo "\n<input type='hidden' name='groupid' value='$groupid'>";
    echo "\n<input type='hidden' name='id' value='$cmid'>";
    echo "\n<input type='hidden' name='questionid' value='$questionid'>";
    echo "\n<input type='hidden' name='evaluate' value='$evaluate'>";
    echo "\n<input type='hidden' name='shownames' value='$shownames'>";
    echo "\n<input type='hidden' name='order' value='$order'>";
    echo "</form>";
    echo "\n</div>";
    echo "\n<style>";
    echo "\n .blinking{";
    echo "\n    animation:blinkingText 0.8s infinite;";
    echo "\n}";
    echo ' @keyframes blinkingText{';
    echo "\n    0%{     color: red;    }";
    echo "\n    50%{    color: transparent; }";
    echo "\n    100%{   color: red;    }";
    echo "\n}";
    echo "\n .blinkhidden{";
    echo "\n    color: transparent;";
    echo "\n}";
    echo "\n</style>";
    echo "\n<script src=\"javascript_teach_refreshG2.js\">";
    echo "\n</script>";
}
if ($histogram) {
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

    echo "\n<image src=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/graph.php?$mygraphinfo\" align='left'>";
    echo "\n</image>";
    $row = array();
    if ($shownames) {
        echo "\n<br /><table border=1><tr><td>Name</td><td>Answer</td></tr>";
        $colorstyle = '';
        for ($i = 0; $i < $numofbars; $i++) {
            if ($evaluate) {
                $colorstyle = "style='".color_style($rag, $fr[$i])."'";
            }
            $namesarray = explode(';;', $mynames[$i]);
            sort($namesarray);
            foreach ($namesarray as $stname) {
                if (strlen($stname) > 0) {
                    $row[] = "\n<tr style='height:10'><td>$stname</td><td $colorstyle>".$choice[$i]."</td></tr>";
                }
            }
        }
        if (count($row) > 0) {
            sort($row);
            foreach ($row as $onerow) {
                echo $onerow;
            }
        }
        echo "\n</table>";
    }
    // Adjust the iframe height so everything fits in just right.
    $iframeheight = 560 + 24 * count($row);
    echo "\n <script>";
    echo "\n iframe = parent.document.getElementById('graphIframe');";
    echo "\n iframe.height = $iframeheight;";
    echo "\n </script>";

} else {
    echo "\n<br />";
    $quizgraphicsurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/multi_tooltip_graph.php";
    $getstring = "id=$cmid&quizid=$quizid&courseid=$courseid&groupid=$groupid&mode=liveviewgrid";
    $getstring .= "&rag=$rag&evaluate=$evaluate&order=$order";
    if ($shownames) {
        echo "<a href='".$quizgraphicsurl."?shownames=0&$getstring'>";
        echo get_string('hidenames', 'quiz_liveviewgrid')."</a>";
    } else {
        echo "<a href='".$quizgraphicsurl."?shownames=1&$getstring'>";
        echo get_string('shownames', 'quiz_liveviewgrid')."</a>";
    }
    foreach ($stans as $usr => $textanswer) {
        echo "\n<br />";
        if ($shownames) {
            $user = $DB->get_record('user', array('id' => $usr));
            echo $user->firstname." ".$user->lastname.": ";
        }
        echo strip_tags($textanswer);
    }
}

/**
 * This function returns the color code to be used in the style for a cell in a table.
 *
 * @param int $rag Indicates if the color scheme is rainbow or RAG.
 * @param float $myfraction The fraction that a student receives for a given answer.
 * @return string The text to be used for the background color.
 */
function color_style($rag, $myfraction) {
    if ($rag == 1) {// Colors from image from Moodle.
        if ($myfraction < 0.01) {
            $redpart = 244;
            $greenpart = 67;
            $bluepart = 54;
        } else if ($myfraction == 1) {
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
    $colorstyle = " background-color: rgb($redpart, $greenpart, $bluepart);";
    return $colorstyle;
}

echo "\n</body>";
echo "\n</html>";
