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
// The function liveview_find_student_gridview($userid).
// The function liveview_who_sofar_gridview($quizid).
// The function liveviewgrid_get_answers($quizid).

require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/locallib.php");
$id = optional_param('id', 0, PARAM_INT);
$cm = $DB->get_record('course_modules', array('id' => $id));
$course = $DB->get_record('course', array('id' => $cm->course));
$quiz = $DB->get_record('quiz', array('id' => $cm->instance));
$hidden = liveviewgrid_update_hidden($course);
foreach ($hidden as $hkey => $hvalue) {
    $$hkey = $hvalue;
}
echo "<html><head>";
echo "<title>".get_string('iframe', 'quiz_liveviewgrid').$quiz->name."</title>";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "\n<link href=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/css/quiz_livereport.css\"
     type=\"text/css\" rel=\"stylesheet\">";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$CFG->wwwroot."/theme/styles.php/".
     $CFG->theme."/".$CFG->themerev."_2/all\" />";
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
$slots = liveviewslots($quizid, $quizcontextid);
$question = liveviewquestion($slots, $singleqid);
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
echo "\n</style>";
// CSS style for the table.
echo "\n<style>";
echo "\n .lrtable {";
echo "\n    text-align: center;";
echo "\n    }";
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
    $trun = 50;
    $dotdot = '....';
}
echo "\n<div style=\"display:none;\" id='timemodified' name=$qmaxtime></div>";
liveviewgrid_display_table($hidden, $showresponses, $quizid, $quizcontextid);
// This is needed to get the column lined up correctly.
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
