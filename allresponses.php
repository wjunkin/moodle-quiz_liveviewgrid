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
 * Creates a statis LIve Report page.
 *
 * @package   quiz_liveviewgrid
 * @copyright 2019 Eckerd College
 * @author    William (Bill) Junkin <junkinwf@eckerd.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/locallib.php");
require_once($CFG->dirroot."/mod/quiz/report/default.php");
require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/classes/quiz_liveviewgrid_fraction.php");
require_once($CFG->dirroot."/question/engine/lib.php");
$rag = optional_param('rag', 1, PARAM_INT);
$evaluate = optional_param('evaluate', 1, PARAM_INT);
$showkey = optional_param('showkey', 1, PARAM_INT);
$order = optional_param('order', 0, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA);
$compact = optional_param('compact', 1, PARAM_INT);
$showanswer = optional_param('showanswer', 0, PARAM_INT);
$shownames = optional_param('shownames', 1, PARAM_INT);
$showaverage = optional_param('showaverage', 1, PARAM_INT);
$slots = array();
$question = array();
$users = array();
$sofar = array();
$cm = $DB->get_record('course_modules', array('id' => $id));
$quizid = $cm->instance;
$quiz = $DB->get_record('quiz', array('id' => $quizid));
echo "<html><head>";
echo "<title>".get_string('allresponses', 'quiz_liveviewgrid').$quiz->name."</title>";
echo "</head><body>";
echo "<h2 style='text-align:center'>".get_string('allresponses', 'quiz_liveviewgrid').$quiz->name."</h2>";
if (isset($_SERVER['HTTP_REFERER'])) {
    echo "\n<br /><a href='".$_SERVER['HTTP_REFERER']."'><button>".get_string('back', 'quiz_liveviewgrid')."</button></a>";
}
echo "<input type=\"button\" class='btn btn-primary' id=\"printbutton\" onclick=\"window.print();\"
    title=\"".get_string('printinfo', 'quiz_liveviewgrid')."\"
    value=\"".get_string('printpage', 'quiz_liveviewgrid')."\" />";
// Check permissions.
$context = context_module::instance($cm->id);
require_capability('mod/quiz:viewreports', $context);

$quizcontextid = $context->id;
$slots = liveviewslots($quizid, $quizcontextid);
$question = liveviewquestion($slots);
$course = $DB->get_record('course', array('id' => $quiz->course));
require_login($course, true, $cm);
$quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
// These arrays are the 'answr' or 'fraction' indexed by userid and questionid.
$stanswers = array();
$stfraction = array();
list($stanswers, $stfraction) = liveviewgrid_get_answers($quizid);
// Check to see if the teacher has permissions to see all groups or the selected group.
$groupmode = groups_get_activity_groupmode($cm, $course);
$currentgroup = groups_get_activity_group($cm, true);
$contextmodule = context_module::instance($cm->id);
$showresponses = false;
if ($groupmode == 1 && !has_capability('moodle/site:accessallgroups', $contextmodule)) {
    if ($group == 0) {
        // Teacher cannot see all groups and no group has been selected.
        $showresponses = false;
        echo get_string('pickgroup', 'quiz_liveviewgrid');
    } else if ($currentgroup > 0) {
        if ($DB->get_record('groups_members', array('groupid' => $group, 'userid' => $USER->id))) {
            // The teacher is a member of this group.
            $showresponses = true;
        } else {
            // Teacher has picked a group but is not a member of this group.
            $showresponses = false;
            echo get_string('notmember', 'quiz_liveviewgrid');
        }
    }
} else {
    $showresponses = true;
}

// The array of hidden values is hidden[].
$hidden = array();
$hidden['id'] = $id;
$hidden['mode'] = $mode;
$hidden['rag'] = $rag;
$hidden['evaluate'] = $evaluate;
$hidden['showkey'] = $showkey;
$hidden['order'] = $order;
$hidden['compact'] = $compact;
$hidden['group'] = $group;
$hidden['showanswer'] = $showanswer;
$hidden['shownames'] = $shownames;
$hidden['showaverage'] = $showaverage;
// Add in the style for the lvdropdown table and javascript for hover.
echo "\n<style>";
echo "\n.lvdropbtn {";
echo "\n  background-color: #3498DB;";
echo "\n  color: white;";
echo "\n  padding: 4px;";
echo "\n  font-size: 16px;";
echo "\n  border: none;";
echo "\n  cursor: pointer;";
echo "\n}";

echo "\n.lvdropbtn:hover, .lvdropbtn:focus {";
echo "\n  background-color: #2980B9;";
echo "\n}";

echo "\n.lvdropdown {";
echo "\n  position: relative;";
echo "\n  display: inline-block;";
echo "\n}";

echo "\n.lvdropdown-content {";
echo "\n  display: none;";
echo "\n  position: absolute;";
echo "\n  background-color: #f1f1f1;";
echo "\n  min-width: 160px;";
echo "\n  overflow: auto;";
echo "\n  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);";
echo "\n  z-index: 1;";
echo "\n}";

echo "\n.lvdropdown-content a {";
echo "\n  color: black;";
echo "\n  text-decoration: none;";
echo "\n  display: block;";
echo "\n}";

echo "\n.lvdropdown a:hover {background-color: #ddd;}";

echo "\n.show {display: block;}";
echo "\n</style>";
echo "\n<button id='button1' type='button' class='btn btn-primary' onclick=\"optionfunction()\">";
echo get_string('clicktodisplay', 'quiz_liveviewgrid')."</button>";
echo "\n<div class='myoptions' id='option1' style=\"display:none;\">";
echo "<form action=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/allresponses.php\">";
echo "<input type='hidden' name='changeoption' value=1>";
echo "<input type='hidden' name='id' value=$id>";
echo "<input type='hidden' name='mode' value=$mode>";
echo "<input type='hidden' name='group' value=$group>";
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
echo "\n<tr>".$td.get_string('showaverage', 'quiz_liveviewgrid')."</td>";
echo $td."<input type='radio' name='showaverage' value=1 ".$checked['showaverage']."> ";
echo get_string('yes', 'quiz_liveviewgrid')."</td>";
echo $td."<input type='radio' name='showaverage' value=0 ".$notchecked['showaverage']."> ";
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
echo "\n<tr>".$td.get_string('correctanswer', 'quiz_liveviewgrid')."</td>";
echo $td."<input type='radio' name='showanswer' value=1 ".$checked['showanswer']."> ";
echo get_string('yes', 'quiz_liveviewgrid')."</td>";
echo $td."<input type='radio' name='showanswer' value=0 ".$notchecked['showanswer']."> ";
echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
echo "\n<tr>".$td.get_string('colorindicategrade', 'quiz_liveviewgrid')."</td>";
echo $td."<input type='radio' name='rag' value=0 ".$checked['rag']."> ";
echo get_string('yes', 'quiz_liveviewgrid')."</td>";
echo $td."<input type='radio' name='rag' value=0 ".$notchecked['rag']."> ";
echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
echo "\n</table>";
$buttontext = get_string('submitoptionchanges', 'quiz_liveviewgrid');
echo "<br /><input type=\"submit\" value=\"$buttontext\"></form>";
echo "</div>";
// Find out if there may be groups. If so, allow the teacher to choose a group.
$canaccess = has_capability('moodle/site:accessallgroups', $contextmodule);
$geturl = $CFG->wwwroot.'/mod/quiz/report/liveviewgrid/allresponses.php';
if ($groupmode) {
    $courseid = $course->id;
    liveviewgrid_group_dropdownmenu($courseid, $geturl, $canaccess, $hidden);
}

if ($showkey && $showresponses) {
    echo get_string('fractioncolors', 'quiz_liveviewgrid')."\n<br />";
    echo "<table border=\"1\" width=\"100%\">\n";
    $head = "<tr>";
    for ($i = 0; $i < 11; $i++) {
        $myfraction = number_format($i / 10, 1, '.', ',');
        $head .= "<td ";
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
        $head .= "style='background-color: rgb($redpart,$greenpart,$bluepart)'";
        $head .= ">$myfraction</td>";
    }
    echo $head."\n</tr></table>";
}
echo get_string('responses', 'quiz_liveviewgrid');
if ($group) {
    $grpname = $DB->get_record('groups', array('id' => $group));
    echo get_string('from', 'quiz_liveviewgrid').$grpname->name;
} else if ($canaccess) {
    echo ' -- ('.get_string('allgroups', 'quiz_liveviewgrid').')';
}
$sofar = liveview_who_sofar_gridview($quizid);
$slots = liveviewslots($quizid, $quizcontextid);
foreach ($slots as $key => $slotvalue) {
    $answer = '';
    $graphicshashurl = '';
    $singleqid = $key;
    if ($showresponses) {
        if ($singleqid > 0) {
            $questiontext = $DB->get_record('question', array('id' => $singleqid));
            $qtext1 = preg_replace('/^<p>/', '', $questiontext->questiontext);
            $qtext2 = preg_replace('/(<br>)*<\/p>$/', '<br />', $qtext1);
            echo "\n<br />".get_string('questionis', 'quiz_liveviewgrid').$qtext2;
            if ($showanswer) {
                if ($questiontext->qtype == 'essay') {
                    $rightanswer = get_string('rightansweressay', 'quiz_liveviewgrid');
                } else {
                    $attempts = $DB->get_records('question_attempts', array('questionid' => $singleqid));
                    foreach ($attempts as $attempt) {
                        $rightanswer = $attempt->rightanswer;
                    }
                }
                echo get_string('rightanswer', 'quiz_liveviewgrid').$rightanswer;
            }
        }
    }

    if ($compact) {
        $trun = 1;
        $dotdot = '';
        // Truncate responses to 4 if compact is desired, else 40 or 200.
    } else {
        $trun = 40;
        $dotdot = '....';
    }
    // Put in a histogram if the question has a histogram and a single question is displayed.
    if ($singleqid > 0) {
        $trun = 200;
        $getvalues = "questionid=".$questiontext->id."&evaluate=$evaluate&courseid=".$quiz->course;
        $getvalues .= "&quizid=$quizid&group=$group&cmid=".$cm->id."&order=$order&shownames=$shownames&showaverage=$showaverage";
        echo "<br /><iframe src=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/singleq_histogram.php?$getvalues\"
            frameBorder=0 height='520' width='100%' id='iframeFor".$questiontext->id."'>";
        echo "</iframe>";
    }
    if ($showresponses) {
        // Script to hide or display the option form.
        echo "\n<script>";
        echo "\nfunction optionfunction() {";
        echo "\n  var e=document.getElementById(\"option1\");";
        echo "\n  var b=document.getElementById(\"button1\");";
        echo "\n  if(e.style.display == \"none\") { ";
        echo "\n      e.style.display = \"block\";";
        echo "\n        b.innerHTML = \"Click to hide options\";";
        echo "\n  } else {";
        echo "\n      e.style.display=\"none\";";
        echo "\n      b.innerHTML = \"Click to display options\";";
        echo "\n  }";
        echo "\n}";
        echo "\n</script>  ";
    }
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
    //echo json_encode($myslots);die;
    foreach ($myslots as $key => $value) {
        $slotsvalue[$key] = $value->slot;
    }
    $qreferences = $DB->get_records('question_references', array('component' => 'mod_quiz',
    'usingcontextid' => $quizcontextid, 'questionarea' => 'slot'));
    foreach ($qreferences as $qreference) {
        $slotid = $qreference->itemid;
        $questionbankentryid = $qreference->questionbankentryid;
        $questionversions = $DB->get_records('question_versions', array('id' => $questionbankentryid));
        foreach ($questionversions as $questionversion) {
            $questionid = $questionversion->questionid;
        }
        $slots[$questionid] = $slotsvalue[$slotid];
    }
    return $slots;
}
/**
 * Function to get the qtype, name, questiontext for each question.
 * @param array $slots and array of slot ids indexed by question ids.
 * @return array $question. A doubly indexed array giving qtype, qname, and qtext for the questions.
 */
function liveviewquestion($slots) {
    global $DB;
    $question = array();
    foreach ($slots as $questionid => $slotvalue) {
        if ($myquestion = $DB->get_record('question', array('id' => $questionid))) {
            $question['qtype'][$questionid] = $myquestion->qtype;
            $question['name'][$questionid] = $myquestion->name;
            $question['questiontext'][$questionid] = $myquestion->questiontext;
        }
    }
    return $question;
}

/**
 * Function to return the code for a button to select settings.
 *
 * @param string $buttontext The text for the button.
 * @param string $hidden The array of the current hidden values.
 * @param string $togglekey The key for the values that are to be toggled.
 * @param string $info The string that gives information in the button tooltip.
 * @return string. The html code for the button form.
 */
function liveview_popout_button($buttontext, $hidden, $togglekey, $info) {
    global $CFG;
    if (strlen($info) > 1) {
        $title = " title=\"$info\"";
    } else {
        $title = '';
    }
    $mytext = "\n<td$title><form action=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/allresponses.php\">";
    foreach ($hidden as $key => $value) {
        // Toggle the value associated with the $togglekey.
        if ($key == $togglekey) {
            if ($value) {
                $value = 0;
            } else {
                $value = 1;
            }
        }
        $mytext .= "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
    }
    $mytext .= "<input type=\"submit\" value=\"$buttontext\"></form></td>";
    return $mytext;
}

echo "</body></html>";
