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
 * Creates the quiz histogram graph for matrix questions (two values per bar).
 * It uses the param values and then uses the /lib/graphlib.php script.
 *
 * It takes the values given to it to create the graph.
 * It does not access any information from the Moodle site.
 * @package   quiz_liveviewgrid
 * @copyright  2012 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->dirroot.'/mod/quiz/report/liveviewgrid/classes/quiz_liveviewgrid_matrixgraphlib.php');
defined('MOODLE_INTERNAL') || die();
$labels = optional_param_array('x', '', PARAM_TEXT);
//$fr = optional_param_array('fr', '', PARAM_FLOAT);
$data = optional_param('data', '', PARAM_TAGLIST);
$datax = optional_param('datax', '', PARAM_TAGLIST);
$total = optional_param('total', '', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$cm = $DB->get_record('course_modules', array('id' => $cmid));
$course = $DB->get_record('course', array('id' => $cm->course));
require_login($course, true, $cm);
$line = new quiz_liveviewgrid_matrixgraphlib(700, 500);
$line->parameter['title']   = '';
$line->parameter['y_label_left'] = get_string('numberofresponses', 'quiz_liveviewgrid');
foreach ($labels as $key => $value) {
    $labels[$key] = urldecode($value);
}

$line->x_data = $labels;
//$line->fractions = $fr;
$line->y_data['responses'] = explode(",", $data);
$line->y_datax['responses'] = explode(",", $datax);
$line->y_format['responses'] = array('colour' => 'blue', 'bar' => 'fill', 'shadow_offset' => 3);
$line->y_order = array('responses');
$line->parameter['y_min_left'] = 0;
$line->parameter['y_max_left'] = $total;
$line->parameter['y_decimal_left'] = 0;
$line->draw();