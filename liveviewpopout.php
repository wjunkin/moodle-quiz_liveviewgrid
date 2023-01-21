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
$singleqid = optional_param('singleqid', 0, PARAM_INT);
$showanswer = optional_param('showanswer', 0, PARAM_INT);
$shownames = optional_param('shownames', 1, PARAM_INT);
$slots = array();
$question = array();
$users = array();
$sofar = array();
$cm = $DB->get_record('course_modules', array('id' => $id));
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
$slots = liveviewslots($quizid, $quizcontextid);
$question = liveviewquestion($slots, $singleqid);
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
        echo "</span><br />";
    }
    echo "\n<button id='button1' type='button'  onclick=\"optionfunction()\">";
    echo get_string('clicktodisplay', 'quiz_liveviewgrid')."</button>";
    echo "\n<div class='myoptions' id='option1' style=\"display:none;\">";
    echo "<form action=\"".$CFG->wwwroot."/mod/quiz/report.php\">";
    echo "<input type='hidden' name='changeoption' value=1>";
    echo "<input type='hidden' name='id' value=$id>";
    echo "<input type='hidden' name='mode' value=$mode>";
    echo "<input type='hidden' name='singleqid' value=$singleqid>";
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
    echo "\n</table>";
    $buttontext = get_string('submitoptionchanges', 'quiz_liveviewgrid');
    echo "<br /><input type=\"submit\" value=\"$buttontext\"></form>";
    echo "</div>";
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

if ($compact) {
    $trun = 1;
    $dotdot = '';
    // Truncate responses to 4 if compact is desired, else 40 or 200.
} else {
    $trun = 40;
    $dotdot = '....';
}
echo "\n<table><tr><td>";
echo get_string('responses', 'quiz_liveviewgrid');
if ($group) {
    $grpname = $DB->get_record('groups', array('id' => $group));
    echo get_string('from', 'quiz_liveviewgrid').$grpname->name;
} else if ($canaccess) {
    echo ' -- ('.get_string('allgroups', 'quiz_liveviewgrid').')';
}

// Find any student who has not sbmitted an answer if names are hidden.

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
if (($singleqid > 0) && (!($shownames))) {
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

    $answertext = get_string('answeredquizno', 'quiz_liveviewgrid');
    if (count($initials) > 0) {
        $noanswer = array();
        $noa = 0;
        $answertext = get_string('answeredqno', 'quiz_liveviewgrid');
        foreach ($initials as $key => $initial) {
            if (isset($stanswers[$key][$singleqid])) {
                $answertext = get_string('answeredall', 'quiz_liveviewgrid');
            } else {
                $noanswer[$noa] = $initial;
                $noa ++;
            }
        }
        asort($noanswer);
        if (count($noanswer) > 0) {
            if (count($noanswer) == 1) {
                $answertext = "1".get_string('answeredonenot', 'quiz_liveviewgrid');
            } else {
                $answertext = count($noanswer).get_string('answeredmanynot', 'quiz_liveviewgrid');
            }
            $content = '';
            foreach ($noanswer as $noans) {
                $content .= "\n<a>$noans</a>";
            }
        }
    }
    echo "<td>";

    echo "\n<div class=\"lvdropdown\" style='width: 100%'>";
    echo "\n<button onclick=\"mylvdropdownFunction()\" class=\"lvdropbtn\" style='width: 100%'";
    if ((count($initials) > 0) && (count($noanswer) > 0)) {
        echo "title='".get_string('answeredinfo', 'quiz_liveviewgrid')."'";
    }
    echo ">";
    echo $answertext;
    echo "\n</button>";
    echo    "\n<div id=\"mylvdropdown\" class=\"lvdropdown-content\">";
    echo    $content;
    echo    "\n</div>";
    echo "\n</div>";

    echo "<script>";
    // When the user clicks on the button, toggle between hiding and showing the lvdropdown content.
    echo "function mylvdropdownFunction() {
      document.getElementById(\"mylvdropdown\").classList.toggle(\"show\");
    }";

    // Close the lvdropdown if the user clicks outside of it.
    echo "\nwindow.onclick = function(event) {";
    echo "\n  if (!event.target.matches('.lvdropbtn')) {";
    echo "\n    var lvdropdowns = document.getElementsByClassName(\"lvdropdown-content\");";
    echo "\n    var i;";
    echo "\n    for (i = 0; i < lvdropdowns.length; i++) {";
    echo "\n      var openlvdropdown = lvdropdowns[i];";
    echo "\n      if (openlvdropdown.classList.contains('show')) {";
    echo "\n        openlvdropdown.classList.remove('show');";
    echo "\n      }";
    echo "\n    }";
    echo "\n  }";
    echo "\n}";
    echo "\n</script>";

    echo "</td>";
}

echo "</tr></table>";

// Put in a histogram if the question has a histogram and a single question is displayed.
if ($singleqid > 0) {
    $trun = 200;
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
        echo $buttontext;
        echo "</td>";
    } else {
        echo "<td></td>";
    }
}
echo "</tr>\n</thead>\n";
$hidden['singleqid'] = $singleqid;

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
    if (isset($users)) {
        echo "\n<tbody>";
        foreach ($users as $user) {
            // Display the row for the student if it is shownames or singleqid == 0 or there is an answer.
            if (($shownames) || ($singleqid == 0) || isset($stanswers[$user][$singleqid])) {
                echo "<tr>";
                if ($shownames) {
                    echo "<td  class=\"first-col\">".liveview_find_student_gridview($user)."</td>\n";
                }
                foreach ($slots as $questionid => $slotvalue) {
                    if (($questionid != "") and ($questionid != 0)) {
                        if (isset($stanswers[$user][$questionid])) {
                            if (isset($stanswers[$user][$questionid])) {
                                if (is_array($stanswers[$user][$questionid]) && (count($stanswers[$user][$questionid] > 1))) {
                                    $answer = '';
                                    foreach ($stanswers[$user][$questionid] as $key => $value) {
                                        $answer .= $key."=".$value."; ";
                                    }
                                } else {
                                    $answer = $stanswers[$user][$questionid];
                                }
                            } else {
                                $answer = ' ';
                            }
                        } else {
                            $answer = ' ';
                        }
                    }
                    $style = '<td';
                    if ($evaluate) {
                        if (isset($stfraction[$user][$questionid]) and (!($stfraction[$user][$questionid] == 'NA'))) {
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
                            echo $style.">&nbsp;".htmlentities($answer)."</td>";
                    } else {
                        // Making a tooltip out of a long answer. The htmlentities function leaves single quotes unchanged.
                        $safeanswer = htmlentities($answer);
                        $safeanswer1 = preg_replace("/\n/", "<br />", $safeanswer);
                        $tooltiptext[] .= "\n    link".$user.'_'.$questionid.": '".addslashes($safeanswer1)."'";
                            echo $style."><div class=\"showTip link".$user.'_'.$questionid."\">";
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