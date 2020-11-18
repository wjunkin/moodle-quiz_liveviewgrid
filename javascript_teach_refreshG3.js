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
 * JavaScript to refresh teacher page in quiz_report_liveviewgrid module when there is a new response.
 *
 * @package    quiz_liveviewgrid
 * @copyright  2020 onwards William F Junkin  <junkinwf@eckerd.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
var http = false;
var x = "";
var myCount = 0;
var start_time = new Date();
var qidhashurl = "qidhash.php" + window.location.search;
if(navigator.appName == "Microsoft Internet Explorer") {
    http = new ActiveXObject("Microsoft.XMLHTTP")
} else {
    http = new XMLHttpRequest();
}

function replace() {
    var milliseconds_since_start = new Date().valueOf() - start_time;
    if(milliseconds_since_start < 3600000) {
        var t = setTimeout("replace()",10000);
        myCount++;
    } else {
        myFunction();
    }
    http.open("GET", qidhashurl, true);
    http.onreadystatechange = function() {
        if(http.readyState == 4) {
            if(http.responseText != x && myCount > 1 && http.responseText > 0){
                window.location = window.location.href + '&x';
            }
            x = http.responseText;
        }
    }
    http.send(null);
}

replace();
function myFunction() {
    document.getElementById('blink1').setAttribute("class", "blinking");
    var bl = document.getElementById('blink1');
    bl.style.display = "block";
}
