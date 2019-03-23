# moodle-quiz_liveviewgrid
Dynamic quiz spreadsheet. <br />
This version v1.2.0 (2019032000) is compatible for Moodle 3.2+.<br />
This quiz report module allows teachers to see, in real time, 
the responses from students as they are completing questions in a quiz.
 As students change their answers or submit more answers, the spreadsheet is refreshed. 
 If desired, the grades that each response would be given can be shown as the background color in the cells of the spreadsheet, 
 but this action is unrelated to the grading of the quiz.
The top row in this spreadsheet/table has the names of the questions in the quiz. 
The teacher can click on any of these question names to obtain an overview of that question.
For multichoice, truefalse, and calculatedmulti question types, a histogram is displayed.
For all other question types, the response from each student is given in one line on the page. 
The teacher can choose to show or hide the student's name associated with each response.<br />
To install this module, place the liveviewgrid directory as a sub-directory in the <your moodle site>/mod/quiz/report/ directory, 
creating the <your moodle site>/mod/quiz/report/liveviewgrid/ directory.
After installing this quiz report module,
 teachers can click on the "Live Report" option in the "Report" drop-down menu to access this spreadsheet.
Changes for version 1.1.3. Spreadsheet handles Cloze questions, has better formatting for tooltips,
  the students can be sorted by last or first name, results can be shown by group or all responses.
  Added "....' to long (truncated) answers to let the teacher know that the tooltip would show more of the answer.
Changes for version 1.2.0. Handles groups properly, including limiting teacher view unless accessallgroups is enabled.
Changes for version 1.2.1. Tooltips now work for touchscreen laptops using Firefos browser.
