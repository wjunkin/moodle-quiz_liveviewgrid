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
 * @copyright 2014 Open University
 * @author    James Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot."/mod/quiz/report/liveviewgrid/classes/quiz_liveviewgrid_fraction.php");
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

    /** @var int either 1 or 0 in the URL get determined by the teachere to show or hide grades of answers. */
    protected $evaluate = 0;
    /** @var int either 1 or 0 in the URL get determined by the teachere to show or hide grading key. */
    protected $showkey = 0;
    /** @var int The time of the last student response to a question. */
    protected $qmaxtime = 0;
    /** @var int The course module id for the quiz. */
    protected $id = 0;
    /** @var String The string that tells the code in quiz/report which sub-module to use. */
    protected $mode = '';
    /** @var int The context id for the quiz. */
    protected $quizcontextid = 0;
    /** @var Array The  array of the students who are attempting the quiz. */
    protected $users = array();
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
        global $OUTPUT, $DB, $CFG;
        $evaluate = optional_param('evaluate', 0, PARAM_INT);
        $showkey = optional_param('showkey', 0, PARAM_INT);
        $id = optional_param('id', 0, PARAM_INT);
        $mode = optional_param('mode', '', PARAM_ALPHA);
        $slots = array();
        $question = array();
        $users = array();
        $quizid = $quiz->id;
        $answer = '';
        $graphicshashurl = '';
        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:viewreports', $this->context);
        $this->print_header_and_tabs($cm, $course, $quiz, 'liveviewgrid');
        $context = $DB->get_record('context', array('instanceid' => $cm->id, 'contextlevel' => 70));
        $quizcontextid = $context->id;
        $slots = $this->liveviewslots($quizid);
        $question = $this->liveviewquestion($slots);
        $quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $quizid));
        // These arrays are the 'answr' or 'fraction' indexed by userid and questionid.
        $stanswers = array();
        $stfraction = array();
        foreach ($quizattempts as $key => $quizattempt) {
            $usrid = $quizattempt->userid;
            $qubaid = $quizattempt->uniqueid;
            $mydm = new quiz_liveviewgrid_fraction($qubaid);
            $qattempts = $DB->get_records('question_attempts', array('questionusageid' => $qubaid));
            foreach ($qattempts as $qattempt) {
                $myresponse = array();
                $qattemptsteps = $DB->get_records('question_attempt_steps', array('questionattemptid' => $qattempt->id));
                foreach ($qattemptsteps as $qattemptstep) {
                    if ($qattemptstep->state == 'complete') {
                        $answers = $DB->get_records('question_attempt_step_data', array('attemptstepid' => $qattemptstep->id));
                        foreach ($answers as $answer) {
                            $myresponse[$answer->name] = $answer->value;
                        }
                        if (count($myresponse) > 0) {
                            $response = $mydm->get_fraction($qattempt->slot, $myresponse);
                            $stanswers[$usrid][$qattempt->questionid] = $response[0];
                            $stfraction[$usrid][$qattempt->questionid] = $response[1];
                        }
                    }
                }
            }
        }

        if ($showkey) {
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
            echo $head."\n</tr></table>\n<br />";
        }

        $qmaxtime = $this->liveviewquizmaxtime($quizcontextid);
        if ($showkey) {
            echo "<a href='".$CFG->wwwroot."/mod/quiz/report.php?id=$id&mode=$mode&evaluate=$evaluate&showkey=0'>";
            echo get_string('hidegradekey', 'quiz_liveviewgrid')."</a>";
        } else {
            echo "<a href='".$CFG->wwwroot."/mod/quiz/report.php?id=$id&mode=$mode&evaluate=$evaluate&showkey=1'>";
            echo get_string('showgradekey', 'quiz_liveviewgrid')."</a>";
        }
        echo "&nbsp&nbsp&nbsp&nbsp";
        if ($evaluate) {
            echo "<a href='".$CFG->wwwroot."/mod/quiz/report.php?id=$id&mode=$mode&evaluate=0&showkey=$showkey'>";
            echo get_string('hidegrades', 'quiz_liveviewgrid')."</a><br />\n";
            echo get_string('gradedexplain', 'quiz_liveviewgrid')."<br />\n";
        } else {
            echo "<a href='".$CFG->wwwroot."/mod/quiz/report.php?id=$id&mode=$mode&evaluate=1&showkey=$showkey' ";
            echo "title=\"".get_string('showgradetitle', 'quiz_liveviewgrid')."\">";
            echo get_string('showgrades', 'quiz_liveviewgrid')."</a><br />\n";
        }
        // CSS style for blinking 'Refresh Page!' notice.
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

        // Javascript and css to make a blinking 'Refresh Page' appear when the page stops refreshing responses.
        echo "\n<div id=\"blink1\" class=\"blinkhidden\">Refresh Page!</div>";
        echo "\n<script>";
        echo "\n  function myFunction() {";
        echo "\n    document.getElementById('blink1').setAttribute(\"class\", \"blinking\");";
        echo "\n }";
        echo "\n</script>";
        echo "Responses\n<br />";
        echo "<table border=\"1\" width=\"100%\" id='timemodified' name=$qmaxtime>\n";
        echo "<thead><tr>";

        echo "<th>".get_string('name', 'quiz_liveviewgrid')."</th>\n";

        foreach ($slots as $key => $slotvalue) {
            echo "<th style=\"word-wrap: break-word;\">";
            if (isset($question['name'][$key])) {
                    $graphurl = $CFG->wwwroot.'/mod/quiz/report/liveviewgrid/quizgraphics.php?question_id='.$key."&quizid=".$quizid;
                    echo "<a href='".$graphurl."' target=\"_blank\">";
                    echo substr(trim(strip_tags($question['name'][$key])), 0, 80);
                    echo "</a>";
            }
            echo "</th>\n";
        }
        echo "</tr>\n</thead>\n";

        $users = $this->liveview_who_sofar_gridview($quizid);

        // Create the table.
        if (isset($users)) {
            foreach ($users as $user) {
                echo "<tbody><tr>";

                echo "<td>".$this->liveview_find_student_gridview($user)."</td>\n";
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
                    echo "<td ";
                    if ($evaluate) {
                        if (isset($stfraction[$user][$questionid]) and (!($stfraction[$user][$questionid] == 'NA'))) {
                            $myfraction = $stfraction[$user][$questionid];
                            $greenpart = intval( 255 * $myfraction);// Add in as much green as the answer is correct.
                            $redpart = intval(255 - $myfraction * 255);// Add in as much red as the answer is not correct.
                            $bluepart = intval(126 * $myfraction);
                            echo "style='background-color: rgb($redpart, $greenpart, $bluepart)'";
                        } else {
                            echo '';
                        }
                    }
                    if (strlen($answer) < 40) {
                        echo ">".htmlentities($answer)."</td>";
                    } else {
                        // Making a tooltip out of a long answer. The htmlentities function leaves single quotes unchanged.
                        $safeanswer = htmlentities($answer);
                        echo "><div title=\"$safeanswer\">".substr(trim(strip_tags($answer)), 0, 40)."</div></td>";
                    }
                }
                echo "</tr></tbody>\n";
            }
        }
        echo "\n</table>";

        // Javascript to refresh the page if the contents of the table change.
        $graphicshashurl = $CFG->wwwroot."/mod/quiz/report/liveviewgrid/graphicshash.php?id=$id";
        // The number of seconds before checking to see if the answers have changed is the $refreshtime.
        $refreshtime = 10;
        $sessionconfig = $DB->get_record('config', array('name' => 'sessiontimeout'));
        $sessiontimeout = $sessionconfig->value;
        $maxrepeat = intval($sessiontimeout/$refreshtime);
        // The number of refreshes without a new answer is $numrefresh.
        $numrefresh = 0;
        $replacetime = $refreshtime*1000;
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
        foreach ($myslots as $key => $value) {
            $slots[$value->questionid] = $value->slot;
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

    /**
     * Return the number of users who have submitted answers to this quiz instance.
     *
     * @param int $quizid The ID for the quiz instance
     * @return array The userids for all the students submitting answers.
     */
    private function liveview_who_sofar_gridview($quizid) {
        global $DB;

        $records = $DB->get_records('quiz_attempts', array('quiz' => $quizid));

        foreach ($records as $records) {
            $userid[] = $records->userid;
        }
        if (isset($userid)) {
            return(array_unique($userid));
        } else {
            return(null);
        }
    }

    /**
     * Return the first and last name of a student.
     *
     * @param int $userid The ID for the student.
     * @return string The last name, first name of the student.
     */
    protected function liveview_find_student_gridview($userid) {
         global $DB;
         $user = $DB->get_record('user', array('id' => $userid));
         $name = $user->lastname.", ".$user->firstname;
         return($name);
    }

}