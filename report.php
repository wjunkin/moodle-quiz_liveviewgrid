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
// The function liveview_button($buttontext, $hidden, $togglekey, $info).
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
        $evaluate = optional_param('evaluate', 0, PARAM_INT);
        $showkey = optional_param('showkey', 0, PARAM_INT);
        $order = optional_param('order', 0, PARAM_INT);
        $group = optional_param('group', 0, PARAM_INT);
        $id = optional_param('id', 0, PARAM_INT);
        $mode = optional_param('mode', '', PARAM_ALPHA);
        $compact = optional_param('compact', 0, PARAM_INT);
        $singleqid = optional_param('singleqid', 0, PARAM_INT);
        $showanswer = optional_param('showanswer', 0, PARAM_INT);
        $shownames = optional_param('shownames', 1, PARAM_INT);
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
        $hidden['evaluate'] = $evaluate;
        $hidden['showkey'] = $showkey;
        $hidden['order'] = $order;
        $hidden['compact'] = $compact;
        $hidden['group'] = $group;
        $hidden['singleqid'] = $singleqid;
        $hidden['showanswer'] = $showanswer;
        $hidden['shownames'] = $shownames;
        $qmaxtime = $this->liveviewquizmaxtime($quizcontextid);
        $sofar = liveview_who_sofar_gridview($quizid);

        if ($showresponses) {
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
            echo liveview_button($buttontext, $hidden, $togglekey, $info);
            if ($evaluate) {
                $buttontext = get_string('hidegrades', 'quiz_liveviewgrid');
                $info = get_string('gradedexplain', 'quiz_liveviewgrid');
            } else {
                $info = get_string('showgradetitle', 'quiz_liveviewgrid');
                $buttontext = get_string('showgrades', 'quiz_liveviewgrid');
            }
            $togglekey = 'evaluate';
            echo liveview_button($buttontext, $hidden, $togglekey, $info);
            if ($shownames) {
                if ($order) {
                    $info = get_string('clickorderlastname', 'quiz_liveviewgrid');
                    $buttontext = get_string('orderlastname', 'quiz_liveviewgrid');
                } else {
                    $info = get_string('clickorderfirstname', 'quiz_liveviewgrid');
                    $buttontext = get_string('orderfirstname', 'quiz_liveviewgrid');
                }
                $togglekey = 'order';
                echo liveview_button($buttontext, $hidden, $togglekey, $info);
            }
            if ($shownames) {
                $buttontext = get_string('hidenames', 'quiz_liveviewgrid');
                $info = get_string('clickhidenames', 'quiz_liveviewgrid');
            } else {
                $buttontext = get_string('shownames', 'quiz_liveviewgrid');
                $info = get_string('clickshownames', 'quiz_liveviewgrid');
            }
            $togglekey = 'shownames';
            echo liveview_button($buttontext, $hidden, $togglekey, $info);
            if ($compact) {
                $buttontext = get_string('expandtable', 'quiz_liveviewgrid');
                $info = get_string('expandexplain', 'quiz_liveviewgrid');
            } else {
                $info = get_string('clickcompact', 'quiz_liveviewgrid');
                $buttontext = get_string('compact', 'quiz_liveviewgrid');
            }
            $togglekey = 'compact';
            echo liveview_button($buttontext, $hidden, $togglekey, $info);
            if ($singleqid > 0) {
                if ($showanswer) {
                    $buttontext = get_string('hidecorrectanswer', 'quiz_liveviewgrid');
                    $info = get_string('clickhideanswer', 'quiz_liveviewgrid');
                } else {
                    $info = get_string('clickshowanswer', 'quiz_liveviewgrid');
                    $buttontext = get_string('showcorrectanswer', 'quiz_liveviewgrid');
                }
                $togglekey = 'showanswer';
                echo liveview_button($buttontext, $hidden, $togglekey, $info);
            }
            echo "</tr></table>";
        }

        // Find out if there may be groups. If so, allow the teacher to choose a group.
        $canaccess = has_capability('moodle/site:accessallgroups', $contextmodule);
        $geturl = $CFG->wwwroot.'/mod/quiz/report.php';
        if ($groupmode) {
            $courseid = $course->id;
            liveviewgrid_group_dropdownmenu($courseid, $geturl, $canaccess, $hidden);
        }
        // If a single question is being displayed, allow the teacher to select a different question.
        if ($singleqid > 0) {
            liveviewgrid_question_dropdownmenu($quizid, $geturl, $hidden);
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
        echo "\n<div id=\"blink1\" class=\"blinkhidden\">".get_string('refreshpage', 'quiz_liveviewgrid')."</div>";
        echo "\n<script>";
        echo "\n  function myFunction() {";
        echo "\n    document.getElementById('blink1').setAttribute(\"class\", \"blinking\");";
        echo "\n }";
        echo "\n</script>";
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
        $togglekey = '';
        echo "</td>";
        echo "\n<td title=\"$info\" style=\"padding: 20px;\">
            <form target='_blank' action=\"".$CFG->wwwroot."/mod/quiz/report/liveviewgrid/liveviewpopout.php\">";
        foreach ($hidden as $key => $value) {
            // Toggle the value associated with the $togglekey.
            if ($key == $togglekey) {
                if ($value) {
                    $value = 0;
                } else {
                    $value = 1;
                }
            }
            echo "\n<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        }
        echo "<input type=\"submit\" value=\"$buttontext\"></form></td>";
        echo "</tr></table>";

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
        // This is needed to get the column lined up correctly.
        echo "\n<div class=\"table-wrapper\">";
        echo "\n<table border=\"1\" width=\"100%\" id='timemodified' name=$qmaxtime>\n";
        echo "<thead><tr>";

        if ($shownames) {
            echo "<th class=\"first-col\">".get_string('name', 'quiz_liveviewgrid')."</th>";
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
            echo "\n<tbody>";
            if (isset($users)) {
                foreach ($users as $user) {
                    echo "<tr>";
                    if ($shownames) {
                        echo "<td  class=\"first-col\">".liveview_find_student_gridview($user)."</td>\n";
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
                                echo " ";
                            }
                        }
                        if ((strlen($answer) < $trun) || ($singleqid > 0)) {
                            echo ">&nbsp;".htmlentities($answer)."</td>";
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
        $refreshtime = 10;
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
            $t = '&t='.time();
            echo "\n numrefresh ++;";
            echo "\n x=document.getElementById('timemodified');";
            echo "\n myname = x.getAttribute('name');";
            echo "\n if(numrefresh < $maxrepeat) {";
            echo "\n    var t=setTimeout(\"replace()\",$replacetime);";
            echo "\n } else {";
            echo "\n myFunction();";
            echo "\n }";
            echo "\nhttp.open(\"GET\", \"".$graphicshashurl.$t."\", true);";
            echo "\nhttp.onreadystatechange=function() {\nif(http.readyState == 4) {";
            echo "\n if(parseInt(http.responseText) != parseInt(myname)){";
            echo "\n    location.reload(true);";
            echo "\n}\n}\n}";
            echo "\n http.send(null);";
            echo "\n}\nreplace();";
        echo "\n</script>";

        return true;
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
