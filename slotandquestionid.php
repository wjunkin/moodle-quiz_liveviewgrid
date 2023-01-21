<?php
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
/**
        $context = $DB->get_record('context', array('instanceid' => $cm->id, 'contextlevel' => 70));
        $quizcontextid = $context->id;echo "\n<br />124 in report.php and quixcontextid is $quizcontextid";
    private function liveviewslots($quizid) {
        global $DB;
        $slots = array();
        $myslots = $DB->get_records('quiz_slots', array('quizid' => $quizid));
        $singleqid = optional_param('singleqid', 0, PARAM_INT);
        foreach ($myslots as $key => $value) {echo "\n<br />debug1114 in report.pphp and singleqid is $singleqid and quizid is $quizid";
            if (($singleqid == 0) || ($value->questionid == $singleqid)) {
                $slots[$value->questionid] = $value->slot;
            }
        }
        return $slots;
    }

*/

$quizcontextid = 21;
$quizid = 2;
$slots = liveviewslots($quizid);
echo "\n<br />debug24 and slots is ".print_r($slots);
function liveviewslots($quizid) {
	global $DB;
	global $quizcontextid;
	$slots = array();
	$slotsvalue = array();
	$myslots = $DB->get_records('quiz_slots', array('quizid' => $quizid));
	$singleqid = optional_param('singleqid', 0, PARAM_INT);
	foreach ($myslots as $key => $value) {echo "\n<br />debug1114 in report.pphp and singleqid is $singleqid and quizid is $quizid";
		if (($singleqid == 0) || ($value->questionid == $singleqid)) {
			$slotsvalue[$key] = $value->slot;
		}
	}
	$qreferences = $DB->get_records('question_references', array('component' => 'mod_quiz', 'usingcontextid' => $quizcontextid, 'questionarea' => 'slot'));
	foreach ($qreferences as $qreference) {
		$slotid = $qreference -> itemid;
		$questionbankentryid = $qreference-> questionbankentryid;
		$questionversions = $DB->get_records('question_versions', array('id' => $questionbankentryid));
		foreach ($questionversions as $questionversion) {
			$questionid = $questionversion->questionid;
		}echo "\n<br />debug44 and questionid is $questionid and slotid is $slotid and questionbankentryid is $questionbankentryid";
		$slots[$questionid] = $slotsvalue[$slotid];
	}
	return $slots;
}

/**	
        foreach ($myslots as $key => $value) {echo "\n<br />debug1114 in report.pphp and singleqid is $singleqid and quizid is $quizid";
            if (($singleqid == 0) || ($value->questionid == $singleqid)) {
                $slotsvalue[$key] = $value->slot;
            }
        }
*/
	
//$qreference = $DB->get_records('question_references', array('component' => 'mod_quiz', 'usingcontextid' => $quizcontextid, 'questionarea' => 'slot'));
//echo "\n<br />debug6 and qreference is ".print_r($qreference);