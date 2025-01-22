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
//xdebug_break();

// Include locallib.php to obtain the functions needed. This includes the following.
// The function liveviewgrid_group_dropdownmenu($courseid, $GETurl, $canaccess, $hidden).
// Thefunction liveview_find_student_gridview($userid).
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
$rowslots = array(); // a readonly copy. LAter on  we use $slots to put in random quiz id 
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
//xdebug_break();
$rowslots = liveviewslotsall($quizid, $quizcontextid); //Twingsister  former livevieslots
asort($rowslots);
$question = liveviewquestionall($rowslots, $singleqid);
//$quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
//xdebug_break();
// These arrays are the 'answr' or 'fraction' indexed by userid and questionid.
$stanswers = array();
$stfraction = array();
$stslot=array();
$stlink=array();
//these are ok even for random questions
//the questionid selected are given
list($stanswers, $stfraction, $stlink,$stslot) = liveviewgrid_get_answers($quizid);
//xdebug_break();
//echo "-----------found these answers---------";
//echo json_encode($stanswers);
//echo json_encode($stfraction);
//echo json_encode($stlink);
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
$hidden['showaverage'] = $showaverage;//Twingsister
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
// This is needed to get the column lined up correctly.
echo "\n<div id=\"container\" style=\"margin-left:1px;margin-top:1px;background:white;\">";
echo "\n<table border=\"1\" width=\"100%\" id='timemodified' class='lrtable' name=$qmaxtime>\n";
echo "<thead><tr>";

if ($shownames) {
    $activestyle = "style='background-size: 20% 100%;
        background-image: linear-gradient(to right, rgba(170, 225, 170, 1) 0%, rgba(230, 255, 230, 1) 100%);
        background-repeat: repeat;'";
    echo "<th class=\"first-col\">".get_string('name', 'quiz_liveviewgrid')." (".count($sofar).")</th>";
}
if ($showaverage) {//twingsister
    echo "<td>Average</td>";
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
//xdebug_break();
foreach ($rowslots as $key => $slotvalue) {
    // Here is the header of the display one column for every question
    // ordetion without a reference (e.g. randomly selected) gets NOREF in the header
        $hidden['singleqid'] = $key;
    if ((!isdummykey($key))&&  isset($question['name'][$key])) { //(!isdummykey($key))
        //echo $key."</br>";
        $safequestionname = trim(strip_tags($question['name'][$key]));
        $buttontext = trim($safequestionname);
        $myquestiontext = preg_replace("/[\r\n]+/", '<br />', $question['questiontext'][$key]);
        if (preg_match('/src=\"@@PLUGINFILE@@/', $myquestiontext, $matches)) {
            $quiz = $DB->get_record('quiz', array('id' => $quizid));
            $qslot = $rowslots[$key];
            $myquestiontext = changepic_url($myquestiontext, $key, $quiz->course, $qslot, $USER->id);
        }
        $ttiptext = get_string('clicksingleq', 'quiz_liveviewgrid').$safequestionname.'<br /><br />'.$myquestiontext;
        // Get rid of any <script> tags that may mess things up.
        $ttiptext = preg_replace("/\<script.*\<\/script\>/m", '', $ttiptext);
        $tooltiptext[] .= "\n    linkqtext_".$key.": '".addslashes($ttiptext)."'";
    } else{$tooltiptext[] .= "\n    linkqtext_".$key.": '"."Randomly selected question"."'";}
        $info = '';
        echo "<td>";
        $linkid = "linkqtext_$key";
        if((!isdummykey($key))){
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
        }else{$buttontext="Random";$hidden;}//$linkid="'Randomquiz'";}
        echo liveview_question_button($buttontext, $hidden, $linkid);
        echo "</td>";
        //echo "<td>NOREF</td>";
    if ((!isdummykey($key))&& $question['qtype'][$key] == 'matrix') { // TWINGSISTER
        // Put in correct row answers for each matrix question.
        $goodans[$key] = array();// The good answer for each row, indexed by row. There each question has unique rowids.
        list($rowtext[$key], $collabel[$key], $goodans[$key], $grademethod[$key]) = goodans($key);
    }
}
// header of the table is out
echo "</tr>\n</thead>\n";
        //echo "<td>AverageMove</td>";// adding student average
        //echo "</td>";
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
    $row=0;
    if (isset($users)) {
        $now = time();
        $firsttime = $now - $activetime * 60;
        echo "\n<tbody>";
        $israndom=array();// keep track of the slots with a randomly selected question
        foreach ($users as $user) {
            // go if singleqid is  dummy
            // Display the row for the student if it is shownames or singleqid == 0 or there is an answer.
            if (($shownames) || ($singleqid == 0) || isdummykey($singleqid)|| isset($stanswers[$user][$singleqid])) {
                echo "<tr>";
                $row=$row+1;
                if ($shownames) {
                    $bgcolor = '';
                    if ($DB->get_records_sql("SELECT id FROM {user} WHERE lastaccess > $firsttime AND id = $user")) {
                        $bgcolor = $activestyle;
                    }
                    echo "<td  class=\"first-col\" $bgcolor>".liveview_find_student_gridview($user)."</td>\n";
                }
                //xdebug_break();
                if ($showaverage) {//Twingsister/ adding student average'';
                    $avg=0.0;
                    //$count=0.0;
                    //$countdummy=0;
                    foreach ($rowslots as $questionid => $slotvalue) {
                        if ((($questionid != "") && ($questionid != 0))&&
                        // old accepting also dummies for randomly questions if ((!isdummykey($questionid)&&($questionid != "") && ($questionid != 0)) &&
                            isset($stfraction[$user][$questionid]) &&(!($stfraction[$user][$questionid] == 'NA')) ) {
                                //$count=$count+1.0;
                                $avg=$avg+$stfraction[$user][$questionid];
                                } //else if (isdummykey($questionid)){$countdummy=$countdummy+1;}
                      }
                    if(isset($stfraction[$user])) {
                        foreach ($stfraction[$user] as $questionid => $slotvalue) {
                            if(!isset($rowslots[$questionid])){// in $slots there is a dummy
                                //$count=$count+1.0;
                                $avg=$avg+$stfraction[$user][$questionid];
                                //$countdummy=$countdummy-1;
                            }
                        }
                      }
                      //if ($count>0 && $countdummy==0) {
                          //$avg=$avg/$count;
                          $avg=$avg/count($rowslots);
                          $stringavg=sprintf('%.02f',$avg*10.00);
                          $avgstr = "<td  ".liveviewgrid_color_for_grade($avg,$rag).">&nbsp; ".$stringavg." </td>";
                      //}else $avgstr="<td></td>";
                    echo $avgstr;
                }
                $myrow = '';
                // put a link if there is a reference
                // dummy questionid must be converted to real questionid before display
                //xdebug_break();
                $knownSlots=$new=unserialize(serialize($stslot[$user])); 
                asort($knownSlots);
                //$slots=$stslot[$user];
                $slots=unserialize(serialize($rowslots)); //hardcopy to $slos that will be changed to have quizid of the randomly selected quizzes 
                $iterator=unserialize(serialize($slots)); 
                foreach ($iterator as $questionid => $slotvalue) {
                    if(isdummykey($questionid)||(isset($israndom[$slotvalue])&& $israndom[$slotvalue])){// useless if the two mapping disagree stslot rulez
                        $israndom[$slotvalue]=true;
                        foreach ($knownSlots as $newquestionid => $tryslotvalue) {
                            if($slotvalue===$tryslotvalue){
                                // delete $slots[$questionid] entry
                                unset($slots[$questionid]);
                                $slots[$newquestionid]=$slotvalue;
                                break;}
                        }
                    }
                }
                asort($slots);
                //  questionid for random selected are now on
                $question = liveviewquestionall($slots, 0);
                $slotswithgrade=0; // Twingsister
                foreach ($slots as $questionid => $slotvalue) {
                    //xdebug_break();
                    // The for above makes this useless if(isdummykey($questionid)){$oldquestionid=$questionid;$questionid=0;}
                    // if it is a randomly selected quiz adjust $questionid
                   //if(!isdummykey($questionid))&&
                    if (isset($stlink[$user][$questionid])) { //Twingsister
                        $link = $stlink[$user][$questionid];
                    } else {
                        $link = '';
                    }
                    if (!isdummykey($questionid)&&($questionid != "") && ($questionid != 0)) {//
                        if (isset($stanswers[$user][$questionid])) {
                            if (is_array($stanswers[$user][$questionid]) && (count($stanswers[$user][$questionid] )> 1)) {
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
                    $style = '<td';// close with $myrow .= $style.">&nbsp;".$answer.$link."</td>";
                    $slotswithgrade=$slotswithgrade+1; // Twingsister also $slotvalue
                    if ($evaluate) {
                        if ((!isdummykey($questionid))&&($questionid != "") && ($questionid != 0)&& $question['qtype'][$questionid] == 'matrix') {
                            $grade = 0;
                            if (strlen($stanswers[$user][$questionid]) > 0) {
                                $myansws = explode(';&nbsp;', $stanswers[$user][$questionid]);
                                $mwrong = 0;// Keeping track of how many wrong answers there are.
                                foreach ($myansws as $myansw) {
                                    if ($myanskey = array_search($myansw, $goodans[$questionid])) {
                                        $mdata[$myanskey] ++;
                                        $correct = 1;
                                        $grade ++;
                                    } else {
                                        // Find the row for the incorrect answer.
                                        $myanskey = 0;
                                        $anssplit = explode(':&nbsp;', $myansw);
                                        $myanskey = array_search($anssplit[0], $rowtext[$questionid]);
                                        $mdatax[$myanskey] ++;
                                        $mwrong ++;
                                    }
                                }
                                $matrixfr = 1;// The fraction for matrix questions, based on grademethod.
                                if ($grademethod[$questionid] == 'kprime') {
                                    if ($mwrong > 0) {
                                        $matrixfr = 0.001;
                                    }
                                } else if ($grademethod[$questionid] == 'kany') {
                                    if ($mwrong > 1) {
                                        $matrixfr = 0.001;
                                    } else if ($mwrong > 0) {
                                        $matrixfr = 0.5;
                                    }
                                } else {
                                    $matrixfr = $grade / count($rowtext[$questionid]) + .001;
                                }
                            } else {
                                $matrixfr = 0;
                            }
                            $stfraction[$user][$questionid] = $matrixfr;
                        }//(!isdummykey($questionid))&&
                        //xdebug_break();
                        // backgroud color set for randomly selected questions too
                        //if (isset($stfraction[$user][$oldquestionid]) && (!($stfraction[$user][$oldquestionid] == 'NA'))) 
                        //xdebug_break(); 
                        if (isset($stfraction[$user][$questionid]) && (!($stfraction[$user][$questionid] == 'NA'))) {
                            //$myfraction = $stfraction[$user][$oldquestionid];
                            $myfraction = $stfraction[$user][$questionid];
                            //echo "here for the color", $myfraction;
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
                        } //else if(isdummykey($questionid)){}
                    }
                    //xdebug_break();   // use $stslot[$user][$questionid]
                    if(($questionid == 0)){
                            $myrow .= $style.">&nbsp;"."Random"."</td>";
                    }else if ( ($singleqid > 0)||(strlen($answer) < $trun) ) { //isdummykey($questionid)||
                            $myrow .= $style.">&nbsp;".$answer.$link."</td>";
                    } else {
                        // Making a tooltip out of a long answer. The htmlentities function leaves single quotes unchanged.
                        $answer = preg_replace("/&nbsp;/", ' ', $answer);// Changing &nbsp; back to a space.
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
                } // all the grades are in the table
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
    //echo "Users quizzing:";echo $row;
    //echo json_encode($tooltiptext); die;
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
