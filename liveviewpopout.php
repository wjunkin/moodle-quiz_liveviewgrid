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
$evaluate = optional_param('evaluate', 0, PARAM_INT);
$showkey = optional_param('showkey', 0, PARAM_INT);
$order = optional_param('order', 0, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$cmid = optional_param('id', 0, PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA);
$compact = optional_param('compact', 0, PARAM_INT);
$singleqid = optional_param('singleqid', 0, PARAM_INT);
$showanswer = optional_param('showanswer', 0, PARAM_INT);
$shownames = optional_param('shownames', 1, PARAM_INT);
$slots = array();
$question = array();
$users = array();
$sofar = array();
$cm = $DB->get_record('course_modules', array('id' => $cmid));
$quizid = $cm->instance;
$quiz = $DB->get_record('quiz', array('id' => $quizid));
echo "<html><head>";
echo "<title>".$quiz->name."</title>";
echo "</head><body>";
echo "<table><tr><td style=\"font-size:200%\">".get_string('notice', 'quiz_liveviewgrid')."</td>";
echo "<td style='padding: 10px'>".get_string('noticeexplain', 'quiz_liveviewgrid');
echo date('d-m-Y H:i')."</td></tr></table>";
$answer = '';
$graphicshashurl = '';
// Check permissions.
$context = context_module::instance($cm->id);
require_capability('mod/quiz:viewreports', $context);

$quizcontextid = $context->id;
$slots = liveviewslots($quizid);
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
$hidden['id'] = $cmid;
$hidden['mode'] = $mode;
$hidden['evaluate'] = $evaluate;
$hidden['showkey'] = $showkey;
$hidden['order'] = $order;
$hidden['compact'] = $compact;
$hidden['group'] = $group;
$hidden['singleqid'] = $singleqid;
$hidden['showanswer'] = $showanswer;
$hidden['shownames'] = $shownames;
$sofar = liveview_who_sofar_gridview($quizid);

if ($showresponses) {
    echo "<span style=\"font-size:125%\">".$quiz->name.get_string('staticpage', 'quiz_liveviewgrid')." </span>";
    echo "<input type=\"button\" id=\"printbutton\" onclick=\"window.print();\"
        title=\"".get_string('printinfo', 'quiz_liveviewgrid')."\"
        value=\"".get_string('printpage', 'quiz_liveviewgrid')."\" />";
    if ($singleqid > 0) {
        $questiontext = $DB->get_record('question', array('id' => $singleqid));
        $qtext1 = preg_replace('/^<p>/', '', $questiontext->questiontext);
        $qtext2 = preg_replace('/(<br>)*<\/p>$/', '<br />', $qtext1);
        echo "<span> ".get_string('questionis', 'quiz_liveviewgrid').$qtext2;
        if ($showanswer) {
            $attempts = $DB->get_records('question_attempts', array('questionid' => $singleqid));
            foreach ($attempts as $attempt) {
                $rightanswer = $attempt->rightanswer;
            }
            echo ": ".get_string('rightanswer', 'quiz_liveviewgrid').$rightanswer;
        }
        echo "</span>";
    }

    echo "<table border = 0><tr>";
    if ($showkey) {
        $info = get_string('clickhidekey', 'quiz_liveviewgrid');
        $buttontext = get_string('hidegradekey', 'quiz_liveviewgrid');
    } else {
        $info = get_string('clickshowkey', 'quiz_liveviewgrid');
        $buttontext = get_string('showgradekey', 'quiz_liveviewgrid');
    }
    $togglekey = 'showkey';
    echo liveview_popout_button($buttontext, $hidden, $togglekey, $info);
    if ($evaluate) {
        $buttontext = get_string('hidegrades', 'quiz_liveviewgrid');
        $info = get_string('gradedexplain', 'quiz_liveviewgrid');
    } else {
        $info = get_string('showgradetitle', 'quiz_liveviewgrid');
        $buttontext = get_string('showgrades', 'quiz_liveviewgrid');
    }
    $togglekey = 'evaluate';
    echo liveview_popout_button($buttontext, $hidden, $togglekey, $info);
    if ($shownames) {
        if ($order) {
            $info = get_string('clickorderlastname', 'quiz_liveviewgrid');
            $buttontext = get_string('orderlastname', 'quiz_liveviewgrid');
        } else {
            $info = get_string('clickorderfirstname', 'quiz_liveviewgrid');
            $buttontext = get_string('orderfirstname', 'quiz_liveviewgrid');
        }
        $togglekey = 'order';
        echo liveview_popout_button($buttontext, $hidden, $togglekey, $info);
    }
    if ($shownames) {
        $buttontext = get_string('hidenames', 'quiz_liveviewgrid');
        $info = get_string('clickhidenames', 'quiz_liveviewgrid');
    } else {
        $buttontext = get_string('shownames', 'quiz_liveviewgrid');
        $info = get_string('clickshownames', 'quiz_liveviewgrid');
    }
    $togglekey = 'shownames';
    echo liveview_popout_button($buttontext, $hidden, $togglekey, $info);
    if ($compact) {
        $buttontext = get_string('expandtable', 'quiz_liveviewgrid');
        $info = get_string('expandexplain', 'quiz_liveviewgrid');
    } else {
        $info = get_string('clickcompact', 'quiz_liveviewgrid');
        $buttontext = get_string('compact', 'quiz_liveviewgrid');
    }
    $togglekey = 'compact';
    echo liveview_popout_button($buttontext, $hidden, $togglekey, $info);
    if ($singleqid > 0) {
        if ($showanswer) {
            $buttontext = get_string('hidecorrectanswer', 'quiz_liveviewgrid');
            $info = get_string('clickhideanswer', 'quiz_liveviewgrid');
        } else {
            $info = get_string('clickshowanswer', 'quiz_liveviewgrid');
            $buttontext = get_string('showcorrectanswer', 'quiz_liveviewgrid');
        }
        $togglekey = 'showanswer';
        echo liveview_popout_button($buttontext, $hidden, $togglekey, $info);
    }
    echo "</tr></table>";
}

// Find out if there may be groups. If so, allow the teacher to choose a group.
$canaccess = has_capability('moodle/site:accessallgroups', $contextmodule);
$geturl = $CFG->wwwroot.'/mod/quiz/report/liveviewgrid/liveviewpopout.php';
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
        $greenpart = intval( 255 * $myfraction);// Add in as much green as the answer is correct.
        $redpart = intval(255 - $myfraction * 255);// Add in as much red as the answer is wrong.
        $bluepart = intval(126 * $myfraction);
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

// Getting and preparing to sorting users.
// The first and last name are in the initials array.
$initials = array();
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
    // Truncate responses to 4 if compact is desired, else 80.
} else {
    $trun = 40;
    $dotdot = '....';
}
// Put in a histogram if the question has a histogram and a single question is displayed.
if ($singleqid > 0) {
    $multitype = array('multichoice', 'truefalse', 'calculatedmulti');
    if (in_array($questiontext->qtype, $multitype)) {
        $getvalues = "questionid=".$questiontext->id."&evaluate=$evaluate&courseid=".$quiz->course;
        $getvalues .= "&quizid=$quizid&group=$group&cmid=".$cm->id."&order=$order&shownames=$shownames";
        echo "<iframe src=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/tooltip_histogram.php?$getvalues\"
            frameBorder=0 height='520' width='800'>";
        echo "</iframe>";
    }
}
echo "<table border=\"1\" width=\"100%\">\n";
echo "<thead><tr>";

if ($shownames) {
    echo "<th>".get_string('name', 'quiz_liveviewgrid')."</th>";
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
        $ttiptext = get_string('clicksingleq', 'quiz_liveviewgrid').$safequestionname.'<br /><br />'.$myquestiontext;
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
        echo $buttontext;
        echo "</td>";
    } else {
        echo "<td></td>";
    }
}
echo "</tr>\n</thead>\n";
$hidden['singleqid'] = $singleqid;

if ($showresponses) {
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
    if (count($initials)) {
        asort($initials);
        foreach ($initials as $newkey => $initial) {
            $users[] = $newkey;
        }
    }
    // Create the table.
    echo "\n<tbody>";
    if (isset($users)) {
        foreach ($users as $user) {
            echo "<tr>";
            if ($shownames) {
                echo "<td>".liveview_find_student_gridview($user)."</td>\n";
            }
            foreach ($slots as $questionid => $slotvalue) {
                if (($questionid != "") and ($questionid != 0)) {
                    if (isset($stanswers[$user][$questionid])) {
                        if (count($stanswers[$user][$questionid]) == 1) {
                            $answer = $stanswers[$user][$questionid];
                        } else {
                            $answer = '';
                            foreach ($stanswers[$user][$questionid] as $key => $value) {
                                $answer .= $key."=".$value."; ";
                            }
                        }
                    } else {
                        $answer = ' ';
                    }
                }
                echo "<td";
                if ($evaluate) {
                    if (isset($stfraction[$user][$questionid]) and (!($stfraction[$user][$questionid] == 'NA'))) {
                        $myfraction = $stfraction[$user][$questionid];
                        $greenpart = intval( 255 * $myfraction);// Add in as much green as the answer is correct.
                        $redpart = intval(255 - $myfraction * 255);// Add in as much red as the answer is not correct.
                        $bluepart = intval(126 * $myfraction);
                        echo " style='background-color: rgb($redpart, $greenpart, $bluepart)'";
                    } else {
                        echo '';
                    }
                }
                if ((strlen($answer) < $trun) || ($singleqid > 0)) {
                    echo ">".htmlentities($answer)."</td>";
                } else {
                    // Making a tooltip out of a long answer. The htmlentities function leaves single quotes unchanged.
                    $safeanswer = htmlentities($answer);
                    $safeanswer1 = preg_replace("/\n/", "<br />", $safeanswer);
                    $tooltiptext[] .= "\n    link".$user.'_'.$questionid.": '".addslashes($safeanswer1)."'";
                    echo "><div class=\"showTip link".$user.'_'.$questionid."\">";
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
                    echo $truncated;
                    echo " $dotdot</div></td>";
                }
            }
            echo "</tr>\n";
        }
        echo "</tbody>";
    }
    echo "\n</table>";

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
} else {
    echo "\n</table>";
}

/**
 * Function to get the questionids as the keys to the $slots array so we know all the questions in the quiz.
 * @param int $quizid The id for this quiz.
 * @return array $slots The slot values (from the quiz_slots table) indexed by questionids.
 */
function liveviewslots($quizid) {
    global $DB;
    $slots = array();
    $myslots = $DB->get_records('quiz_slots', array('quizid' => $quizid));
    $singleqid = optional_param('singleqid', 0, PARAM_INT);
    foreach ($myslots as $key => $value) {
        if (($singleqid == 0) || ($value->questionid == $singleqid)) {
            $slots[$value->questionid] = $value->slot;
        }
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
    $mytext = "\n<td$title><form action=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/liveviewpopout.php\">";
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