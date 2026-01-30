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
 * Quiz liveviewgrid report version information.
 *
 * @package   quiz_liveviewgrid
 * @copyright 2021 William Junkin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026013100;//Twingsister bump
$plugin->requires  = 2022041900;
$plugin->cron      = 18000;
$plugin->component = 'quiz_liveviewgrid';
$plugin->maturity = MATURITY_STABLE;
$plugin->release   = 'v5.0.10 (2023021000) for Moodle 4.0+';
