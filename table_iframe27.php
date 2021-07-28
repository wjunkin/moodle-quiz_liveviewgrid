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
 * Quiz liveviewgrid report class.
 *
 * @package   quiz_liveviewgrid
 * @copyright 2019 Eckerd College
 * @author    William (Bill) Junkin <junkinwf@eckerd.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/classes/quiz_liveviewgrid_fraction.php");
require_once($CFG->dirroot."/question/engine/lib.php");

// Include locallib.php to obtain the functions needed. This includes the following.
// The function liveviewgrid_group_dropdownmenu($courseid, $GETurl, $canaccess, $hidden).
// Thefunction liveview_find_student_gridview($userid).
// The function liveview_who_sofar_gridview($quizid).
// The function liveviewgrid_get_answers($quizid).

require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/locallib.php");
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
$status = optional_param('status', 0, PARAM_INT);
$refresht = optional_param('refresht', 3, PARAM_INT);
$cm = $DB->get_record('course_modules', array('id' => $id));
$course = $DB->get_record('course', array('id' => $cm->course));
$quiz = $DB->get_record('quiz', array('id' => $cm->instance));
if ($lessons = $DB->get_records('lesson', array('course' => $course->id))) {
    $haslesson = 1;
    $lessonid = optional_param('lessonid', 0, PARAM_INT);
    $showlesson = optional_param('showlesson', 0, PARAM_INT);
} else {
    $haslesson = 0;
    $lessonid = 0;
    $showlesson = 0;
}
echo "<html><head>";
echo "<title>".get_string('iframe', 'quiz_liveviewgrid').$quiz->name."</title>";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "\n</head><body>";
$slots = array();
$question = array();
$users = array();
$sofar = array();
$quizid = $quiz->id;
$answer = '';
$graphicshashurl = '';
// Check permissions.
$context = context_module::instance($cm->id);
require_capability('mod/quiz:viewreports', $context);
require_login($course, true, $cm);
$quizcontextid = $context->id;
$slots = liveviewslots($quizid);
$question = liveviewquestion($slots);
$quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
// These arrays are the 'answr' or 'fraction' indexed by userid and questionid.
$stanswers = array();
$stfraction = array();
list($stanswers, $stfraction, $stlink) = liveviewgrid_get_answers($quizid);
// Check to see if the teacher has permissions to see all groups or the selected group.
$groupmode = groups_get_activity_groupmode($cm, $course);
$currentgroup = groups_get_activity_group($cm, true);
$contextmodule = context_module::instance($cm->id);
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
foreach ($hidden as $hiddenkey => $hiddenvalue) {
    if ((!($hiddenkey == 'id')) && (!($hiddenkey == 'singleqid')) && (!($hiddenkey == 'haslesson'))
        && (!($hiddenkey == 'lessonid')) && (!($hiddenkey == 'group'))) {
        // Don't carry group, id, singleqid, haslesson, or lessonid.
        if (isset($_SESSION[$hiddenkey])) {
            $$hiddenkey = $_SESSION[$hiddenkey];
            $hidden[$hiddenkey] = $_SESSION[$hiddenkey];
        }
    }
}
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

$qmaxtime = liveviewquizmaxtime($quizcontextid);
$sofar = liveview_who_sofar_gridview($quizid);
if ($lessonid > 0) {
    $lessonsofar = liveview_who_sofar_lesson($lessonid);
    if (count($lessonsofar) > 0) {
        // Add in those who have started the lesson.
        $allsofar = array_merge($sofar, $lessonsofar);
        $sofar = array_unique($allsofar);
    }
}
if ($showresponses) {
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
    if ($singleqid > 0) {
        $questiontext = $DB->get_record('question', array('id' => $singleqid));
        $qtext1 = preg_replace('/^<p>/', '', $questiontext->questiontext);
        $qtext2 = preg_replace('/(<br>)*<\/p>$/', '<br />', $qtext1);
        echo "\n".get_string('questionis', 'quiz_liveviewgrid').$qtext2;
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
        echo "\n<br />";
    }
}
$canaccess = has_capability('moodle/site:accessallgroups', $contextmodule);
$geturl = $CFG->wwwroot.'/mod/quiz/report.php';
$courseid = $course->id;
// Display progress of lesson. This code is taken from mod/lesson/locallib.php.
// If the code there changes, this will have to be modified accordingly.
if (($lessonid) && (count($sofar))) {
    require_once($CFG->dirroot.'/mod/lesson/locallib.php');
    $lessonmoduleid = $DB->get_record('modules', array('name' => 'lesson'));
    $lmid = $lessonmoduleid->id;
    $cm = $DB->get_record('course_modules', array('instance' => $lessonid, 'course' => $course->id, 'module' => $lmid));
    $lesson = new lesson($DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST), $cm, $course);
    // I can't use any method from the lesson class that uses the $USER global variable.
    $pages = $lesson->load_all_pages();
    $lessonstatus = array();// The array that has the text for lesson status.
    foreach ($sofar as $myuserid) {
        foreach ($pages as $page) {
            if ($page->prevpageid == 0) {
                $pageid = $page->id;  // Find the first page id.
                break;
            }
        }
        if (!$ntries = $DB->count_records("lesson_grades", array("lessonid" => $lessonid, "userid" => $myuserid))) {
            $ntries = 0;// May not be necessary.
        }
        $viewedpageids = array();
        $myparams = array("lessonid" => $lessonid, "userid" => $myuserid, "retry" => $ntries);
        if ($attempts = $DB->get_records('lesson_attempts', $myparams, 'timeseen ASC')) {
            foreach ($attempts as $attempt) {
                $viewedpageids[$attempt->pageid] = $attempt;
            }
        }
        $viewedbranches = array();
        // Collect all of the branch tables viewed.
        if ($branches = $lesson->get_content_pages_viewed($ntries, $myuserid, 'timeseen ASC', 'id, pageid')) {
            foreach ($branches as $branch) {
                $viewedbranches[$branch->pageid] = $branch;
            }
            $viewedpageids = array_merge($viewedpageids, $viewedbranches);
        }
        // Filter out the following pages:
        // - End of Cluster
        // - End of Branch
        // - Pages found inside of Clusters
        // Do not filter out Cluster Page(s) because we count a cluster as one.
        // By keeping the cluster page, we get our 1.
        $validpages = array();
        while ($pageid != 0) {
            $pageid = $pages[$pageid]->valid_page_and_view($validpages, $viewedpageids);
        }

        // Progress calculation as a percent.
        $progress = round(count($viewedpageids) / count($validpages), 2) * 100;
        $lessonstatus[$myuserid] = '';
        if ($ntries > 0) {
            $tr = '';
            if ($ntries == 1) {
                $ty = get_string('try', 'quiz_liveviewgrid');
            } else {
                $ty = get_string('tries', 'quiz_liveviewgrid');
            }
            $lessonstatus[$myuserid] = $ntries.$ty.get_string('completed', 'quiz_liveviewgrid');

        }
        if ($progress > 0) {
            $lessonstatus[$myuserid] .= ' '.get_string('current', 'quiz_liveviewgrid').
                get_string('try', 'quiz_liveviewgrid').$progress.'% '.get_string('completed', 'quiz_liveviewgrid');
        }
        if ($lessonstatus[$myuserid] == '') {
            $lessonstatus[$myuserid] = get_string('lessonnotstarted', 'quiz_liveviewgrid');
        }
    }
}
// CSS style for blinking 'Refresh Page!' notice and making the first column fixed..
echo "\n<style>";
echo "\n .blinking{";
echo "\n    animation:blinkingText 0.8s infinite;";
echo "\n}";
echo "\n @keyframes blinkingText{";
echo "\n    0%{     color: red;    }";
echo "\n    50%{    color: transparent; }";
echo "\n    100%{   color: red;    }";
echo "\n}";
echo "\n .blinkhidden{";
echo "\n    color: transparent;";
echo "\n}";

echo "\n.first-col {";
echo "\n  position: absolute;";
echo "\n	width: 10em;";
echo "\n	margin-left: -10.1em; background:#ffffff;";
echo "\n}";

echo "\n.table-wrapper {";
echo "\n    overflow-x: scroll;";
if ($shownames) {
    echo "\n	margin: 0 0 0 10em;";
} else {
    echo "\n     margin: 0 0 0 0;";
}
echo "\n}";
echo "\n</style>";

// Javascript and css to make a blinking 'Refresh Page' appear when the page stops refreshing responses.
echo "\n<div id=\"blink1\" class=\"blinkhidden\" style=\"display:none;\">";
echo "<form action=\"".$CFG->wwwroot."/mod/quiz/report.php?mode=liveviewgrid\">";
foreach ($hidden as $key => $value) {
    echo "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
}
echo "<input type='submit' value='".get_string('refreshpage', 'quiz_liveviewgrid')."' class=\"blinking\"></form></div>";
echo "\n<script>";
echo "\n  function myFunction() {";
echo "\n    document.getElementById('blink1').setAttribute(\"class\", \"blinking\");";
echo "\n    var bl = document.getElementById('blink1');";
echo "\n    bl.style.display = \"block\";";
echo "\n }";
echo "\n</script>";
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
    // Truncate responses to 4 if compact is desired, else 40 or 200.
} else {
    $trun = 40;
    $dotdot = '....';
}
// Put in a histogram if the question has a histogram and a single question is displayed.
if ($singleqid > 0) {
    $trun = 200;
    $multitype = array('multichoice', 'truefalse', 'calculatedmulti');
    if (in_array($questiontext->qtype, $multitype)) {
        $getvalues = "questionid=".$questiontext->id."&evaluate=$evaluate&courseid=".$quiz->course;
        $getvalues .= "&quizid=$quizid&group=$group&cmid=".$cm->id."&order=$order&shownames=$shownames&rag=$rag";
        echo "<iframe src=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/tooltip_histogram.php?$getvalues\"
            frameBorder=0 height='520' width='800'>";
        echo "</iframe>";
    }
}
// This is needed to get the column lined up correctly.
echo "\n<div class=\"table-wrapper\">";
echo "\n<table border=\"1\" width=\"100%\" id='timemodified' name=$qmaxtime>\n";
echo "<thead><tr>";

if ($shownames) {
    $activestyle = "style='background-size: 20% 100%;
        background-image: linear-gradient(to right, rgba(170, 225, 170, 1) 0%, rgba(230, 255, 230, 1) 100%);
        background-repeat: repeat; text-align:right'";
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
    if (isset($users)) {
        $now = time();
        $firsttime = $now - 300;
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
                    if (($questionid != "") and ($questionid != 0)) {
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
                            $myrow .= $style.">&nbsp;".$answer.$link."</td>";
                    } else {
                        // Making a tooltip out of a long answer. The htmlentities function leaves single quotes unchanged.
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

// Javascript to refresh the page if the contents of the table change.
$graphicshashurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/graphicshash.php?id=$id";
// The number of seconds before checking to see if the answers have changed is the $refreshtime.
$refreshtime = 10 * $refresht;
$sessionconfig = $DB->get_record('config', array('name' => 'sessiontimeout'));
$sessiontimeout = $sessionconfig->value;
$maxrepeat = intval($sessiontimeout / $refreshtime);
// The number of refreshes without a new answer is $numrefresh.
$numrefresh = 0;
$replacetime = $refreshtime * 1000;
echo "\n\n<script type=\"text/javascript\">\nvar http = false;\nvar x=\"\";
        \n\nif(navigator.appName == \"Microsoft Internet Explorer\")
        {\nhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");\n} else {\nhttp = new XMLHttpRequest();}";
echo "\n var numrefresh = $numrefresh;";
echo "\n var maxrepeat = $maxrepeat;";
echo "\n\nfunction replace() { ";
echo "\n    numrefresh ++;";
echo "\n    x=document.getElementById('timemodified');";
echo "\n    myname = x.getAttribute('name');";
echo "\n    if(numrefresh < $maxrepeat) {";
echo "\n       var t=setTimeout(\"replace()\",$replacetime);";
echo "\n    } else {";
echo "\n       myFunction();";
echo "\n    }";
echo "\n    http.open(\"GET\", \"".$graphicshashurl."\", true);";
echo "\n    http.onreadystatechange=function() {";
echo "\n       if(http.readyState == 4) {";
echo "\n          var newresponse = parseInt(http.responseText);";
echo "\n          var priormyname = parseInt(myname);";
echo "\n          if(newresponse == priormyname){";// Don't do anything.
echo "\n             } else {";
echo "\n                location.reload(true);";
echo "\n             }";
echo "\n        }";
echo "\n     }";
echo "\n  http.send(null);";
echo "\n}\nreplace();";
echo "\n</script>";
echo "\n</body></html>";

/**
 * Prints out the drop down menu (form) to select the desired lesson.
 *
 * This is used if there is a lesson and the teacher chooses to show lesson progress.
 * @param int $courseid The ID for the course.
 * @param string $geturl The URL for the form action.
 * @param int $canaccess The integer (1 or 0) if the teacher has necessary permissions.
 * @param array $hidden The hidden option values that are used in the form.
 */
function liveviewlessonmenu($courseid, $geturl, $canaccess, $hidden) {
    global $DB, $USER;
    echo "\n<table border=0><tr>";
    $lessons = $DB->get_records('lesson', array('course' => $courseid));
    echo "\n<td><form action=\"$geturl\">";
    foreach ($hidden as $key => $value) {
        if ($key <> 'lessonid') {
            echo "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        }
    }
    echo "\n<select name=\"lessonid\" onchange='this.form.submit()'>";
    echo "\n<option value=\"0\">".get_string('chooselesson', 'quiz_liveviewgrid')."</option>";
    foreach ($lessons as $lesson) {
        $lessonid = $lesson->id;
        $lessonname = $lesson->name;
        echo "\n<option value=\"$lessonid\">$lessonname</option>";
    }
    echo "\n</select>";
    echo "\n</form></td></tr></table>";
}
/**
 * Return the greatest time that a student responded to a given quiz.
 *
 * This is used to determine if the teacher view of the graph should be refreshed.
 * @param int $quizcontextid The ID for the context for this quiz.
 * @return int The integer for the greatest time.
 */
function liveviewquizmaxtime($quizcontextid) {
    global $DB;
    $quiztime = $DB->get_record_sql("
        SELECT max(qa.timemodified)
        FROM {question_attempts} qa
        JOIN {question_usages} qu ON qu.id = qa.questionusageid
        WHERE qu.contextid = ?", array($quizcontextid));
    $arg = 'max(qa.timemodified)';
    $qmaxtime = intval($quiztime->$arg) + 1;
    return $qmaxtime;
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