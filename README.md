# This repository is Obsolete
Please refer to [THIS](https://github.com/wjunkin/moodle-quiz_liveviewgrid
) repository for a working copy
# moodle-quiz_liveviewgrid
Dynamic quiz spreadsheet.
This plugin, Live Report, is compatible for Moodle 3.2+.

The question types that are supported are: 
- multichoice both with single and multiple answers. 
- truefalse 
- shortanswer
- numerical
- formulas
- essay (do nothing)
- mtf need support https://moodle.org/plugins/qtype_mtf 
- algebra need support https://docs.moodle.org/500/en/Algebra_question_type
- geogebra

This quiz report module allows teachers to see, in real time, the responses from students as they are completing questions in a quiz.

As students change their answers or submit more answers, the spreadsheet is refreshed. If desired, the grades that each response would be given can be shown as the background color in the cells of the spreadsheet and histogram bars, but this action is unrelated to the grading of the quiz.

The top row in this spreadsheet/table has the names of the questions in the quiz. The teacher can click on any of these question names to obtain, in a new tab, an overview of that question.

The spreadsheet and overview windows are dynamic. A static, printable window is also available.

For multichoice, truefalse, and calculatedmulti question types, a histogram is displayed in the overview window.
For all other question types, the response from each student is given in one line on the page.

The teacher has several options: a) Hide/Show students names; b) Sort by first/last names; c) Make spreadsheet compact/expanded.

To install this module, place the liveviewgrid directory as a sub-directory in the <your moodle site>/mod/quiz/report/ directory, creating the <your moodle site>/mod/quiz/report/liveviewgrid/ directory.

After installing this quiz report module, teachers can click on the "Live Report" option in the "Report" drop-down menu to access this spreadsheet.

NOTE that you need to install the [ALGEBRA question type](https://moodle.org/plugins/qtype_algebra/versions) to have a correct live view of the Formula question type. After installing choose EQUIVALENCE as evaluation method.
