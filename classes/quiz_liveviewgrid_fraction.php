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
 * Quiz liveviewgrid quiz_liveviewgrid_fraction class.
 *
 * @package   quiz_liveviewgrid
 * @copyright 2018 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @author    William Junkin <junkinwf@eckerd.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class returns the fractional grade for students answers.
 *
 * For questions that can be graded by the computer program, it returns the fraction associated with
 * the grade assigned to the answer the student has given.
 * A lot of this comes from the question/engine/ scripts.
 * @copyright  2016 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class quiz_liveviewgrid_fraction {
    /**
     * @var question_engine_data_mapper $dm
     */
    public $dm;

    /**
     * Create a new instance of this class. This can be called directly.
     *
     * @param int $qubaid The id of from the question_useages table for this student.
     */
    public function __construct($qubaid) {
        $this->dm = question_engine::load_questions_usage_by_activity($qubaid);
    }

    /**
     * Function to obtain the question from the slot value of the question.
     *
     * @param int $slot The id from the slot table of this question.
     * @return The question object from the row in the question table.
     */
    public function get_question($slot) {
        return $this->dm->get_question($slot);
    }
    /**
     * Function to return the graded responses to the question.
     *
     * @param int $slot The value of the id for this question in the slot table.
     * @param string $myresponse The response that the student gave.
     * @return array ($response[0], $response[1]) The answer and fraction for the answer the student gave.
     */
    public function get_fraction ($slot, $myresponse) {
        $myquestion = $this->dm->get_question($slot);
        $response[0] = 'no summary available';
        if (method_exists($myquestion, 'summarise_response')) {
            $response[0] = $myquestion->summarise_response($myresponse);
        }
        $response[1] = 'NA';
        if (method_exists($myquestion, 'grade_response')
            && is_callable(array($myquestion, 'grade_response'))) {
            $grade = $myquestion->grade_response($myresponse);
            if ($grade[0] == 0) {
                $grade[0] = 0.001;// This is set so that the isset function returns a value of true.
            }
            $response[1] = $grade[0];
        }
        return $response;
    }
    /**
     * Function to return the the linked icon for attachments submitted in teh quiz.
     *
     * @param int $slot The value of the id for this question in the slot table.
     * @return string HTML code for the linked icon for downloading the attachment.
     */
    public function attachment_link($slot) {
        $myoptions = new question_display_options();
        $myoptions->readonly = 1;
        $question = $this->dm->render_question($slot, $myoptions, $number = null);
        $attachment = preg_match("/\<div class\=\"attachments\"(.+?)\<\/div\>/is", $question, $matches);
        $icon = preg_match("/\<a(.+?)\/\>/is", $matches[1], $mtches);
        if ($icon) {
            $attachmenticon = $mtches[0]."</a>";
            return $attachmenticon;
        } else {
            return ' ';
        }
    }

}

