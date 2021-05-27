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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/classes/quiz_liveviewgrid_fraction.php");

// Include locallib.php to obtain the functions needed. This includes the following.
// The function liveviewgrid_group_dropdownmenu($courseid, $GETurl, $canaccess, $hidden).
// Thefunction liveview_find_student_gridview($userid).
// The function liveview_who_sofar_gridview($quizid).
// The function liveviewgrid_get_answers($quizid).

require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/locallib.php");
/**
 * The class quiz_liveviewgrid_report provides a dynamic spreadsheet of the quiz.
 *
 * It gives the most recent answers from all students. It does not do grading.
 * There is an option to show what the grades would be if the quiz were graded at that moment.
 *
 * @copyright 2018 William Junkin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_liveviewgrid_report extends quiz_default_report {

    /** @var context_module context of this quiz.*/
    protected $context;

    /** @var quiz_liveviewgrid_table instance of table class used for main questions stats table. */
    protected $table;

    /** @var int either 1 or 0 in the URL get determined by the teacher to show or hide grades of answers. */
    protected $evaluate = 0;
    /** @var int either 1 or 0 in the URL get determined by the teacher to show or hide grading key. */
    protected $showkey = 0;
    /** @var int either 1 or 0 in the URL get determined by the teacher to order names by first name (1) or last name (0). */
    protected $order = 0;
    /** @var int the id of the group that is being displayed. If the value is 0, results are from all students. */
    protected $group = 0;
    /** @var int The time of the last student response to a question. */
    protected $qmaxtime = 0;
    /** @var int The course module id for the quiz. */
    protected $id = 0;
    /** @var String The string that tells the code in quiz/report which sub-module to use. */
    protected $mode = '';
    /** @var int The context id for the quiz. */
    protected $quizcontextid = 0;
    /** @var Array The sorted array of the students who are attempting the quiz. */
    protected $users = array();
    /** @var Array The array of the students who have attempted the quiz. */
    protected $sofar = array();
    /** @var String The answer submitted to a question. */
    protected $answer = '';
    /** @var String The URL where the program can find out if a new response has been submitted and thus update the spreadsheet. */
    protected $graphicshashurl = '';

    /**
     * Display the report.
     * @param Obj $quiz The object from the quiz table.
     * @param Obj $cm The object from the course_module table.
     * @param Obj $course The object from the course table.
     * @return bool True if successful.
     */
    public function display($quiz, $cm, $course) {
        global $OUTPUT, $DB, $CFG, $USER;
        $changeoption = optional_param('changeoption', 0, PARAM_INT);
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
        if ($lessons = $DB->get_records('lesson', array('course' => $course->id))) {
            $haslesson = 1;
            $lessonid = optional_param('lessonid', 0, PARAM_INT);
            $showlesson = optional_param('showlesson', 0, PARAM_INT);
        } else {
            $haslesson = 0;
            $lessonid = 0;
            $showlesson = 0;
        }
        $slots = array();
        $question = array();
        $users = array();
        $sofar = array();
        $quizid = $quiz->id;
        $answer = '';
        $graphicshashurl = '';
        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:viewreports', $this->context);
        $quiz->name .= get_string('dynamicpage', 'quiz_liveviewgrid');
        $this->print_header_and_tabs($cm, $course, $quiz, 'liveviewgrid');
        $context = $DB->get_record('context', array('instanceid' => $cm->id, 'contextlevel' => 70));
        $quizcontextid = $context->id;
        $slots = $this->liveviewslots($quizid);
        $question = $this->liveviewquestion($slots);
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
                if ($changeoption) {
                    $_SESSION[$hiddenkey] = $hiddenvalue;
                } else {
                    if (isset($_SESSION[$hiddenkey])) {
                        $$hiddenkey = $_SESSION[$hiddenkey];
                        $hidden[$hiddenkey] = $_SESSION[$hiddenkey];
                    }
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

        $qmaxtime = $this->liveviewquizmaxtime($quizcontextid);
        $sofar = liveview_who_sofar_gridview($quizid);
        if ($lessonid > 0) {
            $lessonsofar = liveview_who_sofar_lesson($lessonid);
            if (count($lessonsofar) > 0) {
                // Add in those who have started the lesson.
                $allsofar = array_merge($sofar, $lessonsofar);
                $sofar = array_unique($allsofar);
            }
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $backurl = $_SERVER['HTTP_REFERER'];
            // The request may have come from the iframe.
            $backurl = preg_replace('/report\/liveviewgrid\/table_iframe27/', 'report', $backurl);
            echo "\n<br /><a href='".$backurl."'><button>".get_string('back', 'quiz_liveviewgrid')."</button></a>";
        }
        $allresponsesurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/allresponses.php?";
        $allresponsesurl .= "rag=$rag&evaluate=$evaluate&showkey=$showkey&order=$order&group=$group";
        $allresponsesurl .= "&id=$id&mode=$mode&compact=$compact&showanswer=$showanswer&shownames=$shownames";
        echo "\n<a href='$allresponsesurl'><button>".get_string('viewresponses', 'quiz_liveviewgrid')."</button></a>";
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
/**
                $qtext1 = preg_replace('/^<p>/', '', $questiontext->questiontext);
                $qtext2 = preg_replace('/(<br>)*<\/p>$/', '<br />', $qtext1);
                if (preg_match('/\"\@\@PLUGINFILE\@\@\/(.+?)\"/', $qtext2, $matches)) {
                    // This question has a file. Creating links for files and doing substitution.
                    $filename = $matches[1];
                    $sessionkey = "livereport1q$singleqid";
                    if (isset($_SESSION[$sessionkey])) {
                        $lrqquba = $_SESSION[$sessionkey];
                    } else {
                        $contextid = $DB->get_record('context', array('contextlevel' => 30, 'instanceid' => $USER->id));
                        $ctid = $contextid->id;
                        $data = new stdClass();
                        $data->contextid = $ctid;
                        $data->component = 'core_question_preview';
                        $data->preferredbehaviour = 'deferredfeedback';
                        $insertedid = $DB->insert_record('question_usages', $data);
                        $_SESSION[$sessionkey] = $insertedid;
                        $lrqquba = $_SESSION[$sessionkey];
                        $qa = new stdClass();
                        $qa->questionusageid = $insertedid;
                        $qa->slot = $slots[$singleqid];
                        $qa->behaviour = 'deferredfeedback';
                        // This is sloppy, but maybe it will work with all question types.
                        $qa->behaviour = 'manualgraded';
                        $qa->questionid = $singleqid;
                        $qa->variant = 1;
                        $qa->maxmark = 1.0000000;
                        $qa->minfraction = 0.0000000;
                        $qa->maxfraction = 1.0000000;
                        $qa->questionsummary = $qtext2;
                        $qa->rightanswer = 'NA';
                        $qa->responsesummary = 'NA';
                        $qa->timemodified = time();
                        $qainsertid = $DB->insert_record('question_attempts', $qa);
                    }
                    $coursecontext = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $quiz->course));
                    $ccid = $coursecontext->id;
                    $slotsobject = $DB->get_record('quiz_slots', array('quizid' => $quiz->id, 'questionid' => $singleqid));
                    $myslot = $slotsobject->slot;
                    $filelink = $CFG->wwwroot."/pluginfile.php/$ccid/question/questiontext/$lrqquba/";
                    $basicfilelink = $filelink."$myslot/$singleqid";
                    $filelink .= "1/$singleqid/$filename";
                    $completeq = preg_replace("/\@\@PLUGINFILE\@\@/", $basicfilelink, $qtext2);
                    $qtext2 = $completeq;
                }
*/
                $qtext2 = liveviewgrid_display_question($cm->id, $singleqid);
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
            echo "\n<button id='button1' type='button'  onclick=\"optionfunction()\">";
            echo get_string('clicktodisplay', 'quiz_liveviewgrid')."</button>";
            echo "\n<div class='myoptions' id='option1' style=\"display:none;\">";
            echo "<form action=\"".$CFG->wwwroot."/mod/quiz/report.php\">";
            echo "<input type='hidden' name='changeoption' value=1>";
            echo "<input type='hidden' name='id' value=$id>";
            echo "<input type='hidden' name='mode' value=$mode>";
            echo "<input type='hidden' name='singleqid' value=$singleqid>";
            echo "<input type='hidden' name='group' value=$group>";
            if ($haslesson) {
                echo "<input type='hidden' name='lessonid' value=$lessonid>";
            }
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
            $twait = array(1, 2, 3, 6, 200);
            foreach ($twait as $myt) {
                $tindex = 'refresht'.$myt;
                if ($refresht == $myt) {
                    $checked[$tindex] = 'checked';
                } else {
                    $checked[$tindex] = '';
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
            echo "\n<tr>".$td.get_string('showstatus', 'quiz_liveviewgrid')."</td>";
            echo $td."<input type='radio' name='status' value=1 ".$checked['status']."> ";
            echo get_string('yes', 'quiz_liveviewgrid')."</td>";
            echo $td."<input type='radio' name='status' value=0 ".$notchecked['status']."> ";
            echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
            echo "\n<tr>".$td.get_string('checkt', 'quiz_liveviewgrid')."</td>";
            echo $td."<input type='radio' name='refresht' value=1 ".$checked['refresht1'].">10 ";
            echo " <input type='radio' name='refresht' value=2 ".$checked['refresht2'].">20 ";
            echo " <input type='radio' name='refresht' value=3 ".$checked['refresht3'].">30 ";
            echo " <input type='radio' name='refresht' value=6 ".$checked['refresht6'].">60 ";
            echo " <input type='radio' name='refresht' value=200 ".$checked['refresht200'].">".
                get_string('nevert', 'quiz_liveviewgrid')."</td></tr>";
            if ($haslesson) {
                echo "\n<tr>".$td.get_string('showlessonstatus', 'quiz_liveviewgrid')."</td>";
                echo $td."<input type='radio' name='showlesson' value=1 ".$checked['showlesson']."> ";
                echo get_string('yes', 'quiz_liveviewgrid')."</td>";
                echo $td."<input type='radio' name='showlesson' value=0 ".$notchecked['showlesson']."> ";
                echo get_string('no', 'quiz_liveviewgrid')."</td></tr>";
            }
            echo "\n</table>";
            $buttontext = get_string('submitoptionchanges', 'quiz_liveviewgrid');
            echo "<br /><input type=\"submit\" value=\"$buttontext\"></form>";
            echo "</div>";
        }
        $canaccess = has_capability('moodle/site:accessallgroups', $contextmodule);
        $geturl = $CFG->wwwroot.'/mod/quiz/report.php';
        $courseid = $course->id;
        // Button to select lesson.
        if ($showlesson) {
            $this->liveviewlessonmenu($courseid, $geturl, $canaccess, $hidden);
        }

        // Find out if there may be groups. If so, allow the teacher to choose a group.
        if ($groupmode) {
            liveviewgrid_group_dropdownmenu($courseid, $geturl, $canaccess, $hidden);
        }
        // If a single question is being displayed, allow the teacher to select a different question.
        if ($singleqid > 0) {
            liveviewgrid_question_dropdownmenu($quizid, $geturl, $hidden);
        }
        if ($singleqid > 0) {
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
                        $ntries = 0;  // May not be necessary.
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
        }
        if ($showkey && $showresponses) {
            echo get_string('fractioncolors', 'quiz_liveviewgrid')."\n<br />";
            echo "<table border=\"1\" width=\"100%\">\n";
            $head = "<tr>";
            for ($i = 0; $i < 11; $i++) {
                $myfraction = number_format($i / 10, 1, '.', ',');
                $head .= "<td ";
                if ($rag == 1) {// Colors from image from Moodle.
                    if ($myfraction < 0.001) {
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
        echo "\n<table><tr><td>";
        echo get_string('responses', 'quiz_liveviewgrid');
        if ($group) {
            $grpname = $DB->get_record('groups', array('id' => $group));
            echo get_string('from', 'quiz_liveviewgrid').$grpname->name;
        } else if ($canaccess) {
            echo ' -- ('.get_string('allgroups', 'quiz_liveviewgrid').')';
        }
        $popoutpageurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/liveviewpopout.php";
        $info = get_string('popoutinfo', 'quiz_liveviewgrid');
        $buttontext = get_string('newpage', 'quiz_liveviewgrid');
        echo "</td>";
        echo "\n<td title=\"$info\" style=\"padding: 20px;\">
            <form target='_blank' action=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/liveviewpopout.php\">";
        foreach ($hidden as $key => $value) {
            echo "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        }
        echo "<input type=\"submit\" value=\"$buttontext\"></form></td>";
        if ($singleqid > 0) {
            // Find any student who has not submitted an answer if names are hidden.

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
        if ($shownames) {
            $activestyle = "style='background-size: 20% 100%;
                background-image: linear-gradient(to right, rgba(170, 225, 170, 1) 0%, rgba(230, 255, 230, 1) 100%);
                background-repeat: repeat; text-align:right'";
            echo "\n<table order=0><tr><td>".get_string('howbackground', 'quiz_liveviewgrid')."</td>";
            echo "<td $activestyle>".get_string('name', 'quiz_liveviewgrid')."</td>";
            echo "<td>".get_string('appearifactive', 'quiz_liveviewgrid')."</td></tr></table>";
        }

        if ($singleqid > 0) {
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
                    // Style for users that are currently active in Moodle.
                    $activestyle = "style='background-size: 20% 100%;
            background-image: linear-gradient(to right, rgba(170, 225, 170, 1) 0%, rgba(230, 255, 230, 1) 100%);
            background-repeat: repeat; text-align:right'";
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
                                        if (is_array($stanswers[$user][$questionid])
                                                && (count($stanswers[$user][$questionid] > 1))) {
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
                                            if ($myfraction < 0.001) {
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
                                    // Making a tooltip out of a long answer.
                                    // The htmlentities function leaves single quotes unchanged.
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
                echo "\n</div>";
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
        } else {
            $getvalues = "mode=liveviewgrid&rag=$rag&id=".$cm->id."&evaluate=$evaluate&order=$order&compact=$compact&group=$group";
            $getvalues .= "&showanswer=$showanswer&shownames=$shownames&status=$status&haslesson=$haslesson&showlesson=$showlesson";
            $getvalues .= "&lessonid=$lessonid&refresht=$refresht";
            $tableiframeurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/table_iframe27.php?$getvalues";
            echo "<iframe src=\"$tableiframeurl\" frameBorder=0 height='520' width='100%'>";
            echo "</iframe>";
        }
        return true;
    }
    /**
     * Prints out the drop down menu (form) to select the desired lesson.
     *
     * This is used if there is a lesson and the teacher chooses to show lesson progress.
     * @param int $courseid The ID for the course.
     * @param string $geturl The URL for the form action.
     * @param int $canaccess The integer (1 or 0) if the teacher has necessary permissions.
     * @param array $hidden The hidden option values that are used in the form.
     */
    private function liveviewlessonmenu($courseid, $geturl, $canaccess, $hidden) {
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
    private function liveviewquizmaxtime($quizcontextid) {
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
    private function liveviewslots($quizid) {
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
    private function liveviewquestion($slots) {
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

}