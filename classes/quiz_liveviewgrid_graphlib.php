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
 * Quiz liveviewgrid quiz_liveviewgrid_graphlib class.
 *
 * @package   quiz_liveviewgrid
 * @copyright 2019 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @author    William Junkin <junkinwf@eckerd.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class returns the histogram image for student responses to a single question.
 *
 * A lot of this comes from lib/graphlib.php.
 * @copyright  2019 W. F. Junkin, Eckerd College, http://www.eckerd.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class quiz_liveviewgrid_graphlib {
    /**
     * @var array of input parameters
     */
    public $parameter         = array(        // Input parameters.
        'width'              => 320,          // Default width of image.
        'height'             => 240,          // Default height of image.
        'file_name'          => 'none',        // Name of file for file to be saved as.
                                               // NOTE: no suffix required. this is determined from output_format below.
        'output_format'      => 'PNG',         // Image output format. 'GIF', 'PNG', 'JPEG'. default 'PNG'.

        'seconds_to_live'    => 0,            // Expiry time in seconds (for HTTP header).
        'hours_to_live'      => 0,            // Expiry time in hours (for HTTP header).

        'x_label'            => '',            // If this is set then this text is printed on bottom axis of graph.
        'y_label_left'       => '',            // If this is set then this text is printed on left axis of graph.
        'y_label_right'      => '',            // If this is set then this text is printed on right axis of graph.

        'label_size'         => 8,           // Label text point size.
        'label_font'         => 'default.ttf', // Label text font. don't forget to set 'path_to_fonts' above.
        'label_colour'       => 'gray33',      // Label text colour.
        'y_label_angle'      => 90,           // Rotation of y axis label.

        'x_label_angle'      => 90,            // Rotation of y axis label.

        'outer_padding'      => 5,            // Padding around outer text. i.e. title, y label, and x label.
        'inner_padding'      => 0,            // Padding beteen axis text and graph.
        'x_inner_padding'      => 5,            // Padding beteen axis text and graph.
        'y_inner_padding'      => 6,            // Padding beteen axis text and graph.
        'outer_border'       => 'none',        // Colour of border aound image, or 'none'.
        'inner_border'       => 'black',       // Colour of border around actual graph, or 'none'.
        'inner_border_type'  => 'box',         // The 'box' for all four sides, 'axis' for x/y axis only,
                                               // The 'y' or 'y-left' for y axis only, 'y-right' for right y axis only,
                                               // The 'x' for x axis only, 'u' for both left and right y axis and x axis.
        'outer_background'   => 'none',        // Background colour of entire image.
        'inner_background'   => 'none',        // Background colour of plot area.

        'y_min_left'         => 0,            // This will be reset to minimum value if there is a value lower than this.
        'y_max_left'         => 0,            // This will be reset to maximum value if there is a value higher than this.
        'y_min_right'        => 0,            // This will be reset to minimum value if there is a value lower than this.
        'y_max_right'        => 0,            // This will be reset to maximum value if there is a value higher than this.
        'x_min'              => 0,            // Only used if x axis is numeric.
        'x_max'              => 0,            // Only used if x axis is numeric.

        'y_resolution_left'  => 1,            // Scaling for rounding of y axis max value.
                                               // If max y value is 8645 then
                                               // If y_resolution is 0, then y_max becomes 9000.
                                               // If y_resolution is 1, then y_max becomes 8700.
                                               // If y_resolution is 2, then y_max becomes 8650.
                                               // If y_resolution is 3, then y_max becomes 8645.
                                               // Get it?
        'y_decimal_left'     => 0,            // Number of decimal places for y_axis text.
        'y_resolution_right' => 2,            // The same for right hand side.
        'y_decimal_right'    => 0,            // The same for right hand side.
        'x_resolution'       => 2,            // Only used if x axis is numeric.
        'x_decimal'          => 0,            // Only used if x axis is numeric.

        'point_size'         => 4,            // Default point size. use even number for diamond or triangle to get nice look.
        'brush_size'         => 4,            // Default brush size for brush line.
        'brush_type'         => 'circle',      // Type of brush to use to draw line. choose from the following.
                                               // The circle, square, horizontal, vertical, slash, or backslash.
        'bar_size'           => 0.8,          // Size of bar to draw. <1 bars won't touch.
                                               // If  1 is full width - i.e. bars will touch.
                                               // If  >1 means bars will overlap.
        'bar_spacing'        => 10,           // Space in pixels between group of bars for each x value.
        'shadow_offset'      => 3,            // Draw shadow at this offset, unless overidden by data parameter.
        'shadow'             => 'grayCC',      // The 'none' or colour of shadow.
        'shadow_below_axis'  => true,         // Whether to draw shadows of bars and areas below the x/zero axis.


        'x_axis_gridlines'   => 'auto',        // If set to a number then x axis is treated as numeric.
        'y_axis_gridlines'   => 6,            // Number of gridlines on y axis.
        'zero_axis'          => 'none',        // Colour to draw zero-axis, or 'none'.


        'axisfont'          => 'default.ttf', // Axis text font. don't forget to set 'path_to_fonts' above.
        'axissize'          => 8,            // Axis text font size in points
        'axiscolour'        => 'gray33',      // Colour of axis text.
        'y_axisangle'       => 0,            // Rotation of axis text.
        'x_axisangle'       => 0,            // Rotation of axis text.

        'y_axis_text_left'   => 1,            // Whether to print left hand y axis text. if 0 no text, if 1 all ticks have text,
        'x_axis_text'        => 1,            // If 4 then print every 4th tick and text, etc...
        'y_axis_text_right'  => 0,            // Behaviour same as above for right hand y axis.

        'x_offset'           => 0.5,          // The x axis tick offset from y axis as fraction of tick spacing.
        'y_ticks_colour'     => 'black',       // Colour to draw y ticks, or 'none'.
        'x_ticks_colour'     => 'black',       // Colour to draw x ticks, or 'none'.
        'y_grid'             => 'line',        // Grid lines. set to 'line' or 'dash'...
        'x_grid'             => 'line',        // Or if set to 'none' print nothing.
        'grid_colour'        => 'grayEE',      // Default grid colour.
        'tick_length'        => 4,            // Length of ticks in pixels. can be negative. i.e. outside data drawing area.

        'legend'             => 'none',        // Default. no legend.
                                              // Otherwise: top-left, top-right, bottom-left, bottom-right.
                                              // Or outside-top, outside-bottom, outside-left, or outside-right.
        'legend_offset'      => 10,           // Offset in pixels from graph or outside border.
        'legend_padding'     => 5,            // Padding around legend text.
        'legend_font'        => 'default.ttf',   // Legend text font. don't forget to set 'path_to_fonts' above.
        'legend_size'        => 8,            // Legend text point size.
        'legend_colour'      => 'black',       // Legend text colour.
        'legend_border'      => 'none',        // Legend border colour, or 'none'.

        'decimal_point'      => '.',           // Symbol for decimal separation  '.' or ',' *european support.
        'thousand_sep'       => ',',           // Symbol for thousand separation ',' or ''.

    );
    /**
     * @var Array of text values for y-axis tick labels.
     */
    public $yticklabels     = null;
    /**
     * @var Array of offsets for different sets of data.
     */
    public $offsetrelation   = null;

    /**
     * Function to initialize the values for this class..
     *
     */
    public function init() {

        // Moodle. mods:  overrides the font path and encodings.

        global $CFG;

        // A default.ttf is searched for in this order:dataroot/lang/xx_local/fonts, dataroot/lang/xx/fonts, dirroot/lang/xx/fonts.
        // Then dataroot/lang, lib/.

        $currlang = current_language();
        if (file_exists("$CFG->dataroot/lang/".$currlang."_local/fonts/default.ttf")) {
            $fontpath = "$CFG->dataroot/lang/".$currlang."_local/fonts/";
        } else if (file_exists("$CFG->dataroot/lang/$currlang/fonts/default.ttf")) {
            $fontpath = "$CFG->dataroot/lang/$currlang/fonts/";
        } else if (file_exists("$CFG->dirroot/lang/$currlang/fonts/default.ttf")) {
            $fontpath = "$CFG->dirroot/lang/$currlang/fonts/";
        } else if (file_exists("$CFG->dataroot/lang/default.ttf")) {
            $fontpath = "$CFG->dataroot/lang/";
        } else {
            $fontpath = "$CFG->libdir/";
        }

        $this->parameter['path_to_fonts'] = $fontpath;

        // End Moodle mods.

        $this->calculated['outer_border'] = $this->calculated['boundary_box'];

        // Outer padding.
        $this->calculated['boundary_box']['left']   += $this->parameter['outer_padding'];
        $this->calculated['boundary_box']['top']    += $this->parameter['outer_padding'];
        $this->calculated['boundary_box']['right']  -= $this->parameter['outer_padding'];
        $this->calculated['boundary_box']['bottom'] -= $this->parameter['outer_padding'];

        $this->init_x_axis();
        $this->init_y_axis();
        $this->init_legend();
        $this->init_labels();

        // Take into account tick lengths.
        $this->calculated['bottom_inner_padding'] = $this->parameter['x_inner_padding'];
        if (($this->parameter['x_ticks_colour'] != 'none') && ($this->parameter['tick_length'] < 0)) {
            $this->calculated['bottom_inner_padding'] -= $this->parameter['tick_length'];
        }
        $this->calculated['boundary_box']['bottom'] -= $this->calculated['bottom_inner_padding'];

        $this->calculated['left_inner_padding'] = $this->parameter['y_inner_padding'];
        if ($this->parameter['y_axis_text_left']) {
            if (($this->parameter['y_ticks_colour'] != 'none') && ($this->parameter['tick_length'] < 0)) {
                $this->calculated['left_inner_padding'] -= $this->parameter['tick_length'];
            }
        }
        $this->calculated['boundary_box']['left'] += $this->calculated['left_inner_padding'];

        $this->calculated['right_inner_padding'] = $this->parameter['y_inner_padding'];
        if ($this->parameter['y_axis_text_right']) {
            if (($this->parameter['y_ticks_colour'] != 'none') && ($this->parameter['tick_length'] < 0)) {
                $this->calculated['right_inner_padding'] -= $this->parameter['tick_length'];
            }
        }
        $this->calculated['boundary_box']['right'] -= $this->calculated['right_inner_padding'];

        // BoundaryBox now has coords for plotting area.
        $this->calculated['inner_border'] = $this->calculated['boundary_box'];

        $this->init_data();
        $this->init_x_ticks();
        $this->init_y_ticks();
    }
    /**
     * Function to draw the text, border, etc. on the image.
     *
     */
    public function draw_text() {
        $colour = $this->parameter['outer_background'];
        if ($colour != 'none') {
            $this->draw_rectangle($this->calculated['outer_border'], $colour, 'fill'); // Graph background.
        }

        // Draw border around image.
        $colour = $this->parameter['outer_border'];
        if ($colour != 'none') {
            $this->draw_rectangle($this->calculated['outer_border'], $colour, 'box'); // Graph border.
        }

        $this->draw_title();
        $this->draw_x_label();
        $this->draw_y_label_left();
        $this->draw_y_label_right();
        $this->draw_x_axis();
        $this->draw_y_axis();
        if ($this->calculated['y_axis_left']['has_data']) {
            $this->draw_zero_axis_left();  // Either draw zero axis on left.
        } else if ($this->calculated['y_axis_right']['has_data']) {
            $this->draw_zero_axis_right(); // Or right.
        }
        $this->draw_legend();

        // Draw border around plot area.
        $colour = $this->parameter['inner_background'];
        if ($colour != 'none') {
            $this->draw_rectangle($this->calculated['inner_border'], $colour, 'fill'); // Graph background.
        }

        // Draw border around image.
        $colour = $this->parameter['inner_border'];
        if ($colour != 'none') {
            $this->draw_rectangle($this->calculated['inner_border'],
                $colour, $this->parameter['inner_border_type']); // Graph border.
        }
    }
    /**
     * Function to do all the drawing that this class does.
     *
     */
    public function draw() {
        $this->init();
        $this->draw_text();
        $this->draw_data();
        $this->output();
    }

    /**
     * Function to initialize the sets of data used in creating the image.
     *
     * @param array $set The array of values used for the drawing style of the histogram.
     * @param bool $offset Is there a shadow (1) around the histogram.
     */
    public function draw_set($set, $offset) {
        if ($offset) {
            @$this->init_variable($colour, $this->y_format[$set]['shadow'], $this->parameter['shadow']);
        } else {
            $colour  = $this->y_format[$set]['colour'];
        }
        @$this->init_variable($point,      $this->y_format[$set]['point'],      'none');
        @$this->init_variable($pointsize,  $this->y_format[$set]['point_size'],  $this->parameter['point_size']);
        @$this->init_variable($line,       $this->y_format[$set]['line'],       'none');
        @$this->init_variable($brushtype,  $this->y_format[$set]['brush_type'],  $this->parameter['brush_type']);
        @$this->init_variable($brushsize,  $this->y_format[$set]['brush_size'],  $this->parameter['brush_size']);
        @$this->init_variable($bar,        $this->y_format[$set]['bar'],        'none');
        @$this->init_variable($barsize,    $this->y_format[$set]['bar_size'],    $this->parameter['bar_size']);
        @$this->init_variable($area,       $this->y_format[$set]['area'],       'none');

        $lastx = 0;
        $lasty = 'none';
        $fromx = 0;
        $fromy = 'none';

        $lvcolours = array('red', 'orange', 'black', 'yellow', 'ltred', 'ltorange', 'lime', 'gray', 'green', 'ltgreen', 'green');
        $myfractions = $this->fractions;
        foreach ($this->x_data as $index => $x) {
            $thisy = $this->calculated['y_plot'][$set][$index];
            $thisx = $this->calculated['x_plot'][$index];

            if (($bar != 'none') && (string)$thisy != 'none') {
                if ($relatedset = $this->offsetrelation[$set]) {                               // Moodle.
                    $yoffset = $this->calculated['y_plot'][$relatedset][$index];                // Moodle.
                } else {                                                                        // Moodle.
                    $yoffset = 0;                                                               // Moodle.
                }                                                                               // Moodle.
                if (isset($myfractions[$index])) {
                    $myfraction = $myfractions[$index];
                    $greenpart = intval(67 + 212 * $myfraction - 84 * $myfraction * $myfraction);
                    $redpart = intval(244 + 149 * $myfraction - 254 * $myfraction * $myfraction);
                    if ($redpart > 255) {
                        $redpart = 255;
                    }
                    $bluepart = intval(54 - 236 * $myfraction + 256 * $myfraction * $myfraction);
                    $this->colour['grade'] = imagecolorallocate ($this->image, $redpart, $greenpart, $bluepart);
                    $mycolor = 'grade';
                } else {
                    $mycolor = $colour;
                }
                $this->bar($thisx, $thisy, $bar, $barsize, $mycolor, $offset, $set, $yoffset);   // Moodle.
            }

            if (($area != 'none') && (((string)$lasty != 'none') && ((string)$thisy != 'none'))) {
                $this->area($lastx, $lasty, $thisx, $thisy, $area, $colour, $offset);
            }

            if (($point != 'none') && (string)$thisy != 'none') {
                $this->plot($thisx, $thisy, $point, $pointsize, $colour, $offset);
            }

            if (($line != 'none') && ((string)$thisy != 'none')) {
                if ((string)$fromy != 'none') {
                    $this->line($fromx, $fromy, $thisx, $thisy, $line, $brushtype, $brushsize, $colour, $offset);
                }

                $fromy = $thisy; // Start next line from here.
                $fromx = $thisx;
            } else {
                $fromy = 'none';
                $fromx = 'none';
            }

            $lastx = $thisx;
            $lasty = $thisy;
        }
    }

    /**
     * Function to draw the bars for the data.
     *
     */
    public function draw_data() {
        // Cycle thru y data to be plotted.
        // First check for drop shadows...
        foreach ($this->y_order as $order => $set) {
            @$this->init_variable($offset, $this->y_format[$set]['shadow_offset'], $this->parameter['shadow_offset']);
            @$this->init_variable($colour, $this->y_format[$set]['shadow'], $this->parameter['shadow']);
            if ($colour != 'none') {
                $this->draw_set($set, $offset);
            }

        }

        // Then draw data.
        foreach ($this->y_order as $order => $set) {
            $this->draw_set($set, 0);
        }
    }

    /**
     * Function to draw the legend for the histogram. (It usually doesn't have a legend.
     *
     */
    public function draw_legend() {
        $position      = $this->parameter['legend'];
        if ($position == 'none') {
            return; // Abort if no border.
        }

        $bordercolour  = $this->parameter['legend_border'];
        $offset        = $this->parameter['legend_offset'];
        $padding       = $this->parameter['legend_padding'];
        $height        = $this->calculated['legend']['boundary_box_all']['height'];
        $width         = $this->calculated['legend']['boundary_box_all']['width'];
        $graphtop      = $this->calculated['boundary_box']['top'];
        $graphbottom   = $this->calculated['boundary_box']['bottom'];
        $graphleft     = $this->calculated['boundary_box']['left'];
        $graphright    = $this->calculated['boundary_box']['right'];
        $outsideright  = $this->calculated['outer_border']['right'];
        $outsidebottom = $this->calculated['outer_border']['bottom'];
        switch ($position) {
            case 'top-left':
                $top    = $graphtop + $offset;
                $bottom = $graphtop + $height + $offset;
                $left   = $graphleft + $offset;
                $right  = $graphleft + $width + $offset;

              break;
            case 'top-right':
                $top    = $graphtop + $offset;
                $bottom = $graphtop + $height + $offset;
                $left   = $graphright - $width - $offset;
                $right  = $graphright - $offset;

                break;
            case 'bottom-left':
                $top    = $graphbottom - $height - $offset;
                $bottom = $graphbottom - $offset;
                $left   = $graphleft + $offset;
                $right  = $graphleft + $width + $offset;

                break;
            case 'bottom-right':
                $top    = $graphbottom - $height - $offset;
                $bottom = $graphbottom - $offset;
                $left   = $graphright - $width - $offset;
                $right  = $graphright - $offset;
                break;

            case 'outside-top' :
                $top    = $graphtop;
                $bottom = $graphtop + $height;
                $left   = $outsideright - $width - $offset;
                $right  = $outsideright - $offset;
                break;

            case 'outside-bottom' :
                $top    = $graphbottom - $height;
                $bottom = $graphbottom;
                $left   = $outsideright - $width - $offset;
                $right  = $outsideright - $offset;
                break;

            case 'outside-left' :
                $top    = $outsidebottom - $height - $offset;
                $bottom = $outsidebottom - $offset;
                $left   = $graphleft;
                $right  = $graphleft + $width;
                break;

            case 'outside-right' :
                $top    = $outsidebottom - $height - $offset;
                $bottom = $outsidebottom - $offset;
                $left   = $graphright - $width;
                $right  = $graphright;
                break;
            default: // Default is top left. no particular reason.
                $top    = $this->calculated['boundary_box']['top'];
                $bottom = $this->calculated['boundary_box']['top'] + $this->calculated['legend']['boundary_box_all']['height'];
                $left   = $this->calculated['boundary_box']['left'];
                $right  = $this->calculated['boundary_box']['right'] + $this->calculated['legend']['boundary_box_all']['width'];

        }
        // Legend border.
        if ($bordercolour != 'none') {
            $this->draw_rectangle(array('top' => $top,
                'left' => $left,
                'bottom' => $bottom,
                'right' => $right), $this->parameter['legend_border'], 'box');
        }

        // Legend text.
        $legendtext = array('points' => $this->parameter['legend_size'],
            'angle'  => 0,
            'font'   => $this->parameter['legend_font'],
            'colour' => $this->parameter['legend_colour']);

        $box = $this->calculated['legend']['boundary_box_max']['height']; // Use max height for legend square size.
        $x = $left + $padding;
        $xtext = $x + $box * 2;
        $y = $top + $padding;

        foreach ($this->y_order as $set) {
            $legendtext['text'] = $this->calculated['legend']['text'][$set];
            if ($legendtext['text'] != 'none') {
                // If text exists then draw box and text.
                $boxcolour = $this->colour[$this->y_format[$set]['colour']];

                // Draw box.
                imagefilledrectangle($this->image, $x, $y, $x + $box, $y + $box, $boxcolour);

                // Draw text.
                $coords = array('x' => $x + $box * 2, 'y' => $y, 'reference' => 'top-left');
                $legendtext['boundary_box'] = $this->calculated['legend']['boundary_box'][$set];
                $this->update_boundarybox($legendtext['boundary_box'], $coords);
                $this->print_ttf($legendtext);
                $y += $padding + $box;
            }
        }

    }

    /**
     * Function to draw the labels on the right. (The labels are usually on the left.
     *
     */
    public function draw_y_label_right() {
        if (!$this->parameter['y_label_right']) {
            return;
            exit;
        }
        $x = $this->calculated['boundary_box']['right'] + $this->parameter['y_inner_padding'];
        if ($this->parameter['y_axis_text_right']) {
            $x += $this->calculated['y_axis_right']['boundary_box_max']['width']
                                                 + $this->calculated['right_inner_padding'];
        }
        $y = ($this->calculated['boundary_box']['bottom'] + $this->calculated['boundary_box']['top']) / 2;

        $label = $this->calculated['y_label_right'];
        $coords = array('x' => $x, 'y' => $y, 'reference' => 'left-center');
        $this->update_boundarybox($label['boundary_box'], $coords);
        $this->print_ttf($label);
    }


    /**
     * Function to draw the labels for the left (y) axis).
     *
     */
    public function draw_y_label_left() {
        if (!$this->parameter['y_label_left']) {
            return;
        }
        $x = $this->calculated['boundary_box']['left'] - $this->parameter['y_inner_padding'];
        if ($this->parameter['y_axis_text_left']) {
            $x -= $this->calculated['y_axis_left']['boundary_box_max']['width']
                                                 + $this->calculated['left_inner_padding'];
        }
        $y = ($this->calculated['boundary_box']['bottom'] + $this->calculated['boundary_box']['top']) / 2;

        $label = $this->calculated['y_label_left'];
        $coords = array('x' => $x, 'y' => $y, 'reference' => 'right-center');
        $this->update_boundarybox($label['boundary_box'], $coords);
        $this->print_ttf($label);
    }

    /**
     * Function to draw the title for the histogram. Usually there is no title.
     *
     */
    public function draw_title() {
        if (!$this->parameter['title']) {
            return;
            exit;
        }
        $y = $this->calculated['boundary_box']['top'] - $this->parameter['outer_padding'];
        $x = ($this->calculated['boundary_box']['right'] + $this->calculated['boundary_box']['left']) / 2;
        $label = $this->calculated['title'];
        $coords = array('x' => $x, 'y' => $y, 'reference' => 'bottom-center');
        $this->update_boundarybox($label['boundary_box'], $coords);
        $this->print_ttf($label);
    }

    /**
     * Function to draw the labels for the x-axis.
     *
     */
    public function draw_x_label() {
        if (!$this->parameter['x_label']) {
            return;
        }
        $y = $this->calculated['boundary_box']['bottom'] + $this->parameter['x_inner_padding'];
        if ($this->parameter['x_axis_text']) {
            $y += $this->calculated['x_axis']['boundary_box_max']['height']
                                                + $this->calculated['bottom_inner_padding'];
        }
        $x = ($this->calculated['boundary_box']['right'] + $this->calculated['boundary_box']['left']) / 2;
        $label = $this->calculated['x_label'];
        $coords = array('x' => $x, 'y' => $y, 'reference' => 'top-center');
        $this->update_boundarybox($label['boundary_box'], $coords);
        $this->print_ttf($label);
    }

    /**
     * Function to draw the zero of the axis on the left.
     *
     */
    public function draw_zero_axis_left() {
        $colour = $this->parameter['zero_axis'];
        if ($colour == 'none') {
            return;
            exit;
        }
        // Draw zero axis on left hand side.
        $this->calculated['zero_axis'] = round($this->calculated['boundary_box']['top']
            + ($this->calculated['y_axis_left']['max'] * $this->calculated['y_axis_left']['factor']));
        imageline($this->image, $this->calculated['boundary_box']['left'], $this->calculated['zero_axis'],
        $this->calculated['boundary_box']['right'], $this->calculated['zero_axis'], $this->colour[$colour]);
    }

    /**
     * Function to draw the zero axis on the right. The zero of the axis is usually on the left.
     *
     */
    public function draw_zero_axis_right() {
        $colour = $this->parameter['zero_axis'];
        if ($colour == 'none') {
            return;
            exit;
        }
        // Draw zero axis on right hand side.
        $this->calculated['zero_axis'] = round($this->calculated['boundary_box']['top']
            + ($this->calculated['y_axis_right']['max'] * $this->calculated['y_axis_right']['factor']));
        imageline($this->image, $this->calculated['boundary_box']['left'], $this->calculated['zero_axis'],
            $this->calculated['boundary_box']['right'], $this->calculated['zero_axis'], $this->colour[$colour]);
    }

    /**
     * Function to draw the x-axis.
     *
     */
    public function draw_x_axis() {
        $gridcolour  = $this->colour[$this->parameter['grid_colour']];
        $tickcolour  = $this->colour[$this->parameter['x_ticks_colour']];
        $axiscolour  = $this->parameter['axiscolour'];
        $xgrid       = $this->parameter['x_grid'];
        $gridtop     = $this->calculated['boundary_box']['top'];
        $gridbottom  = $this->calculated['boundary_box']['bottom'];

        if ($this->parameter['tick_length'] >= 0) {
            $ticktop     = $this->calculated['boundary_box']['bottom'] - $this->parameter['tick_length'];
            $tickbottom  = $this->calculated['boundary_box']['bottom'];
            $textbottom  = $tickbottom + $this->calculated['bottom_inner_padding'];
        } else {
            $ticktop     = $this->calculated['boundary_box']['bottom'];
            $tickbottom  = $this->calculated['boundary_box']['bottom'] - $this->parameter['tick_length'];
            $textbottom  = $tickbottom + $this->calculated['bottom_inner_padding'];
        }

        $axisfont    = $this->parameter['axisfont'];
        $axissize    = $this->parameter['axissize'];
        $axisangle   = $this->parameter['x_axisangle'];

        if ($axisangle == 0) {
            $reference = 'top-center';
        }
        if ($axisangle > 0) {
            $reference = 'top-right';
        }
        if ($axisangle < 0) {
            $reference = 'top-left';
        }
        if ($axisangle == 90) {
            $reference = 'top-center';
        }

        // Generic tag information. applies to all axis text.
        $axistag = array('points' => $axissize, 'angle' => $axisangle, 'font' => $axisfont, 'colour' => $axiscolour);

        foreach ($this->calculated['x_axis']['tick_x'] as $set => $tickx) {
            // Draw x grid if colour specified.
            if ($xgrid != 'none') {
                switch ($xgrid) {
                    case 'line':
                        imageline($this->image, round($tickx), round($gridtop), round($tickx), round($gridbottom), $gridcolour);
                        break;
                    case 'dash':
                        imagedashedline($this->image, round($tickx), round($gridtop), round($tickx),
                            round($gridbottom), $gridcolour);
                        break;
                }
            }

            if ($this->parameter['x_axis_text'] && !($set % $this->parameter['x_axis_text'])) { // Test if tick should be displayed.
                // Draw tick.
                if ($tickcolour != 'none') {
                    imageline($this->image, round($tickx), round($ticktop), round($tickx), round($tickbottom), $tickcolour);
                }

                // Draw axis text.
                $coords = array('x' => $tickx, 'y' => $textbottom, 'reference' => $reference);
                $axistag['text'] = $this->calculated['x_axis']['text'][$set];
                $axistag['boundary_box'] = $this->calculated['x_axis']['boundary_box'][$set];
                $this->update_boundarybox($axistag['boundary_box'], $coords);
                $this->print_ttf($axistag);
            }
        }
    }

    /**
     * Function to draw the y-axis.
     *
     */
    public function draw_y_axis() {
        $gridcolour  = $this->colour[$this->parameter['grid_colour']];
        $tickcolour  = $this->colour[$this->parameter['y_ticks_colour']];
        $axiscolour  = $this->parameter['axiscolour'];
        $ygrid       = $this->parameter['y_grid'];
        $gridleft    = $this->calculated['boundary_box']['left'];
        $gridright   = $this->calculated['boundary_box']['right'];

        // Axis font information.
        $axisfont    = $this->parameter['axisfont'];
        $axissize    = $this->parameter['axissize'];
        $axisangle   = $this->parameter['y_axisangle'];
        $axistag = array('points' => $axissize, 'angle' => $axisangle, 'font' => $axisfont, 'colour' => $axiscolour);

        if ($this->calculated['y_axis_left']['has_data']) {
            // LEFT HAND SIDE.
            // Left and right coords for ticks.
            if ($this->parameter['tick_length'] >= 0) {
                $tickleft     = $this->calculated['boundary_box']['left'];
                $tickright    = $this->calculated['boundary_box']['left'] + $this->parameter['tick_length'];
            } else {
                $tickleft     = $this->calculated['boundary_box']['left'] + $this->parameter['tick_length'];
                $tickright    = $this->calculated['boundary_box']['left'];
            }
            $textright      = $tickleft - $this->calculated['left_inner_padding'];

            if ($axisangle == 0) {
                $reference = 'right-center';
            }
            if ($axisangle > 0) {
                $reference = 'right-top';
            }
            if ($axisangle < 0) {
                $reference = 'right-bottom';
            }
            if ($axisangle == 90) {
                $reference = 'right-center';
            }

            foreach ($this->calculated['y_axis']['tick_y'] as $set => $ticky) {
                // Draw y grid if colour specified.
                if ($ygrid != 'none') {
                    switch ($ygrid) {
                        case 'line':
                            imageline($this->image, round($gridleft), round($ticky), round($gridright), round($ticky), $gridcolour);
                            break;
                        case 'dash':
                            imagedashedline($this->image, round($gridleft), round($ticky),
                              round($gridright), round($ticky), $gridcolour);
                            break;
                    }
                }

                // Y axis text.
                if ($this->parameter['y_axis_text_left'] && !($set % $this->parameter['y_axis_text_left'])) {
                    // Draw tick.
                    if ($tickcolour != 'none') {
                        imageline($this->image, round($tickleft), round($ticky), round($tickright), round($ticky), $tickcolour);
                    }

                    // Draw axis text...
                    $coords = array('x' => $textright, 'y' => $ticky, 'reference' => $reference);
                    $axistag['text'] = $this->calculated['y_axis_left']['text'][$set];
                    $axistag['boundary_box'] = $this->calculated['y_axis_left']['boundary_box'][$set];
                    $this->update_boundarybox($axistag['boundary_box'], $coords);
                    $this->print_ttf($axistag);
                }
            }
        }

        if ($this->calculated['y_axis_right']['has_data']) {
            // RIGHT HAND SIDE.
            // Left and right coords for ticks.
            if ($this->parameter['tick_length'] >= 0) {
                $tickleft     = $this->calculated['boundary_box']['right'] - $this->parameter['tick_length'];
                $tickright    = $this->calculated['boundary_box']['right'];
            } else {
                $tickleft     = $this->calculated['boundary_box']['right'];
                $tickright    = $this->calculated['boundary_box']['right'] - $this->parameter['tick_length'];
            }
            $textleft       = $tickright + $this->calculated['left_inner_padding'];

            if ($axisangle == 0) {
                $reference = 'left-center';
            }
            if ($axisangle > 0) {
                $reference = 'left-bottom';
            }
            if ($axisangle < 0) {
                $reference = 'left-top';
            }
            if ($axisangle == 90) {
                $reference = 'left-center';
            }

            foreach ($this->calculated['y_axis']['tick_y'] as $set => $ticky) {
                if (!$this->calculated['y_axis_left']['has_data'] && $ygrid != 'none') { // Draw grid if not drawn already (above).
                    switch ($ygrid) {
                        case 'line':
                            imageline($this->image, round($gridleft), round($ticky), round($gridright), round($ticky), $gridcolour);
                            break;
                        case 'dash':
                            imagedashedline($this->image, round($gridleft), round($ticky),
                                round($gridright), round($ticky), $gridcolour);
                            break;
                    }
                }

                if ($this->parameter['y_axis_text_right'] && !($set % $this->parameter['y_axis_text_right'])) {
                    // Draw tick.
                    if ($tickcolour != 'none') {
                        imageline($this->image, round($tickleft), round($ticky), round($tickright), round($ticky), $tickcolour);
                    }

                    // Draw axis text...
                    $coords = array('x' => $textleft, 'y' => $ticky, 'reference' => $reference);
                    $axistag['text'] = $this->calculated['y_axis_right']['text'][$set];
                    $axistag['boundary_box'] = $this->calculated['y_axis_left']['boundary_box'][$set];
                    $this->update_boundarybox($axistag['boundary_box'], $coords);
                    $this->print_ttf($axistag);
                }
            }
        }
    }

    /**
     * Function to initialize the data used in creating the image.
     *
     */
    public function init_data() {
        $this->calculated['y_plot'] = array(); // Array to hold pixel plotting coords for y axis.
        $height = $this->calculated['boundary_box']['bottom'] - $this->calculated['boundary_box']['top'];
        $width  = $this->calculated['boundary_box']['right'] - $this->calculated['boundary_box']['left'];

        // Calculate pixel steps between axis ticks.
        $this->calculated['y_axis']['step'] = $height / ($this->parameter['y_axis_gridlines'] - 1);

        // Calculate x ticks spacing taking into account x offset for ticks.
        $extratick  = 2 * $this->parameter['x_offset']; // Extra tick to account for padding.
        $numticks = $this->calculated['x_axis']['num_ticks'] - 1;    // Number of x ticks.

        // Hack by rodger to avoid division by zero, see bug 1231.
        if ($numticks == 0) {
            $numticks = 1;
        }

        $this->calculated['x_axis']['step'] = $width / ($numticks + $extratick);
        $widthplot = $width - ($this->calculated['x_axis']['step'] * $extratick);
        $this->calculated['x_axis']['step'] = $widthplot / $numticks;

        // Calculate factor for transforming x,y physical coords to logical coords for right hand y_axis.
        $yrange = $this->calculated['y_axis_right']['max'] - $this->calculated['y_axis_right']['min'];
        $yrange = ($yrange ? $yrange : 1);
        $this->calculated['y_axis_right']['factor'] = $height / $yrange;

        // Calculate factor for transforming x,y physical coords to logical coords for left hand axis.
        $yranges = $this->calculated['y_axis_left']['max'] - $this->calculated['y_axis_left']['min'];
        $yranges = ($yranges ? $yranges : 1);
        $this->calculated['y_axis_left']['factor'] = $height / $yranges;
        if ($this->parameter['x_axis_gridlines'] != 'auto') {
            $xranges = $this->calculated['x_axis']['max'] - $this->calculated['x_axis']['min'];
            $xranges = ($xranges ? $xranges : 1);
            $this->calculated['x_axis']['factor'] = $widthplot / $xranges;
        }

        // Cycle thru all data sets...
        $this->calculated['num_bars'] = 0;
        foreach ($this->y_order as $order => $set) {
            // Determine how many bars there are.
            if (isset($this->y_format[$set]['bar']) && ($this->y_format[$set]['bar'] != 'none')) {
                $this->calculated['bar_offset_index'][$set] = $this->calculated['num_bars']; // Index to relate bar with data set.
                $this->calculated['num_bars']++;
            }

            // Calculate y coords for plotting data.
            foreach ($this->x_data as $index => $x) {
                $this->calculated['y_plot'][$set][$index] = $this->y_data[$set][$index];

                if ((string)$this->y_data[$set][$index] != 'none') {

                    if (isset($this->y_format[$set]['y_axis']) && $this->y_format[$set]['y_axis'] == 'right') {
                        $this->calculated['y_plot'][$set][$index] =
                            round(($this->y_data[$set][$index] - $this->calculated['y_axis_right']['min'])
                                * $this->calculated['y_axis_right']['factor']);
                    } else {
                        $this->calculated['y_plot'][$set][$index] =
                            round(($this->y_data[$set][$index] - $this->calculated['y_axis_left']['min'])
                                * $this->calculated['y_axis_left']['factor']);
                    }

                }
            }
        }
        if ($this->calculated['num_bars']) {
            $xstep       = $this->calculated['x_axis']['step'];
            $totalwidth  = $this->calculated['x_axis']['step'] - $this->parameter['bar_spacing'];
            $barwidth    = $totalwidth / $this->calculated['num_bars'];

            $barx = ($barwidth - $totalwidth) / 2; // Starting x offset.
            for ($i = 0; $i < $this->calculated['num_bars']; $i++) {
                $this->calculated['bar_offset_x'][$i] = $barx;
                $barx += $barwidth; // Add width of bar to x offset.
            }
            $this->calculated['bar_width'] = $barwidth;
        }
    }

    /**
     * Function to initialize the x ticks for the histogram.
     *
     */
    public function init_x_ticks() {
        $xstep       = $this->calculated['x_axis']['step'];
        $ticksoffset = $this->parameter['x_offset']; // Where to start drawing ticks relative to y axis.
        $gridleft    = $this->calculated['boundary_box']['left'] + ($xstep * $ticksoffset); // Grid x start.
        $tickx       = $gridleft; // Tick x coord.

        foreach ($this->calculated['x_axis']['text'] as $set => $value) {
            $this->calculated['x_axis']['tick_x'][$set] = $tickx;
            if ($this->parameter['x_axis_gridlines'] == 'auto') {
                $this->calculated['x_plot'][$set] = round($tickx);
            }
            $tickx += $xstep;
        }

        $gridx = $gridleft;
        if (empty($this->calculated['x_axis']['factor'])) {
            $this->calculated['x_axis']['factor'] = 0;
        }
        if (empty($this->calculated['x_axis']['min'])) {
            $this->calculated['x_axis']['min'] = 0;
        }
        $factor = $this->calculated['x_axis']['factor'];
        $min = $this->calculated['x_axis']['min'];

        if ($this->parameter['x_axis_gridlines'] != 'auto') {
            foreach ($this->x_data as $index => $x) {
                $offset = $x - $this->calculated['x_axis']['min'];
                $this->calculated['x_plot'][$index] = $gridleft + ($x - $min) * $factor;
            }
        }
    }

    /**
     * Function to initialize the ticks for the y-axis.
     *
     */
    public function init_y_ticks() {
        // Get coords for y axis ticks.

        $ystep      = $this->calculated['y_axis']['step'];
        $gridbottom = $this->calculated['boundary_box']['bottom'];
        $ticky      = $gridbottom; // Tick y coord.

        for ($i = 0; $i < $this->parameter['y_axis_gridlines']; $i++) {
            $this->calculated['y_axis']['tick_y'][$i] = $ticky;
            $ticky   -= $ystep;
        }

    }

    /**
     * Function to initialize the data for labels.
     *
     */
    public function init_labels() {
        if ($this->parameter['title']) {
            $size = $this->get_boundarybox(
                array('points' => $this->parameter['title_size'],
                      'angle'  => 0,
                      'font'   => $this->parameter['title_font'],
                      'text'   => $this->parameter['title']));
            $this->calculated['title']['boundary_box']  = $size;
            $this->calculated['title']['text']         = $this->parameter['title'];
            $this->calculated['title']['font']         = $this->parameter['title_font'];
            $this->calculated['title']['points']       = $this->parameter['title_size'];
            $this->calculated['title']['colour']       = $this->parameter['title_colour'];
            $this->calculated['title']['angle']        = 0;

            $this->calculated['boundary_box']['top'] += $size['height'] + $this->parameter['outer_padding'];

        } else {
            $this->calculated['title']['boundary_box'] = $this->get_null_size();
        }

        if ($this->parameter['y_label_left']) {
            $this->calculated['y_label_left']['text']    = $this->parameter['y_label_left'];
            $this->calculated['y_label_left']['angle']   = $this->parameter['y_label_angle'];
            $this->calculated['y_label_left']['font']    = $this->parameter['label_font'];
            $this->calculated['y_label_left']['points']  = $this->parameter['label_size'];
            $this->calculated['y_label_left']['colour']  = $this->parameter['label_colour'];

            $size = $this->get_boundarybox($this->calculated['y_label_left']);
            $this->calculated['y_label_left']['boundary_box']  = $size;
            $this->calculated['boundary_box']['left'] += $size['width'];

        } else {
            $this->calculated['y_label_left']['boundary_box'] = $this->get_null_size();
        }

        if ($this->parameter['y_label_right']) {
            $this->calculated['y_label_right']['text']    = $this->parameter['y_label_right'];
            $this->calculated['y_label_right']['angle']   = $this->parameter['y_label_angle'];
            $this->calculated['y_label_right']['font']    = $this->parameter['label_font'];
            $this->calculated['y_label_right']['points']  = $this->parameter['label_size'];
            $this->calculated['y_label_right']['colour']  = $this->parameter['label_colour'];

            $size = $this->get_boundarybox($this->calculated['y_label_right']);
            $this->calculated['y_label_right']['boundary_box']  = $size;
            $this->calculated['boundary_box']['right'] -= $size['width'];

        } else {
            $this->calculated['y_label_right']['boundary_box'] = $this->get_null_size();
        }

        if ($this->parameter['x_label']) {
            $this->calculated['x_label']['text']         = $this->parameter['x_label'];
            $this->calculated['x_label']['angle']        = $this->parameter['x_label_angle'];
            $this->calculated['x_label']['font']         = $this->parameter['label_font'];
            $this->calculated['x_label']['points']       = $this->parameter['label_size'];
            $this->calculated['x_label']['colour']       = $this->parameter['label_colour'];

            $size = $this->get_boundarybox($this->calculated['x_label']);
            $this->calculated['x_label']['boundary_box']  = $size;
            $this->calculated['boundary_box']['bottom'] -= $size['height'];

        } else {
            $this->calculated['x_label']['boundary_box'] = $this->get_null_size();
        }

    }


    /**
     * Function to initialize the legend. Usually there is no legend.
     *
     */
    public function init_legend() {
        $this->calculated['legend'] = array(); // Array to hold calculated values for legend.
        $this->calculated['legend']['boundary_box_max'] = $this->get_null_size();
        if ($this->parameter['legend'] == 'none') {
            return;
            exit;
        }

        $position = $this->parameter['legend'];
        $numsets = 0; // Number of data sets with legends.
        $sumtextheight = 0; // Total of height of all legend text items.
        $width = 0;
        $height = 0;

        foreach ($this->y_order as $set) {
             $text = isset($this->y_format[$set]['legend']) ? $this->y_format[$set]['legend'] : 'none';
             $size = $this->get_boundarybox(
                  array('points' => $this->parameter['legend_size'],
                       'angle'  => 0,
                       'font'   => $this->parameter['legend_font'],
                       'text'   => $text));

             $this->calculated['legend']['boundary_box'][$set] = $size;
             $this->calculated['legend']['text'][$set]        = $text;

            if ($text && $text != 'none') {
                 $numsets++;
                 $sumtextheight += $size['height'];
            }

            if ($size['width'] > $this->calculated['legend']['boundary_box_max']['width']) {
                  $this->calculated['legend']['boundary_box_max'] = $size;
            }
        }

        $offset  = $this->parameter['legend_offset'];  // Offset in pixels of legend box from graph border.
        $padding = $this->parameter['legend_padding']; // Padding in pixels around legend text.
        $textwidth = $this->calculated['legend']['boundary_box_max']['width']; // Width of largest legend item.
        $textheight = $this->calculated['legend']['boundary_box_max']['height']; // Use height as size for colour square in legend.
        $width = $padding * 2 + $textwidth + $textheight * 2;  // Left and right padding + maximum text width + space for square.
        $height = ($padding + $textheight) * $numsets + $padding; // Top and bottom padding + padding between text + text.

        $this->calculated['legend']['boundary_box_all'] = array('width'     => $width,
                                                              'height'    => $height,
                                                              'offset'    => $offset,
                                                              'reference' => $position);

        switch ($position) { // Move in right or bottom if legend is outside data plotting area.
            case 'outside-top' :
                $this->calculated['boundary_box']['right']      -= $offset + $width; // Move in right hand side.
                break;

            case 'outside-bottom' :
                $this->calculated['boundary_box']['right']      -= $offset + $width; // Move in right hand side.
                break;

            case 'outside-left' :
                $this->calculated['boundary_box']['bottom']      -= $offset + $height; // Move in right hand side.
                break;

            case 'outside-right' :
                $this->calculated['boundary_box']['bottom']      -= $offset + $height; // Move in right hand side.
                break;
        }
    }

    /**
     * Function to initialize the y-axis.
     *
     */
    public function init_y_axis() {
        $this->calculated['y_axis_left'] = array(); // Array to hold calculated values for y_axis on left.
        $this->calculated['y_axis_left']['boundary_box_max'] = $this->get_null_size();
        $this->calculated['y_axis_right'] = array(); // Array to hold calculated values for y_axis on right.
        $this->calculated['y_axis_right']['boundary_box_max'] = $this->get_null_size();

        $axisfont       = $this->parameter['axisfont'];
        $axissize       = $this->parameter['axissize'];
        $axiscolour     = $this->parameter['axiscolour'];
        $axisangle      = $this->parameter['y_axisangle'];
        $yticklabels   = $this->yticklabels;

        $this->calculated['y_axis_left']['has_data'] = false;
        $this->calculated['y_axis_right']['has_data'] = false;

        // Find min and max y values.
        $minleft = $this->parameter['y_min_left'];
        $maxrleft = $this->parameter['y_max_left'];
        $minright = $this->parameter['y_min_right'];
        $maxright = $this->parameter['y_max_right'];
        $dataleft = array();
        $dataright = array();
        foreach ($this->y_order as $order => $set) {
            if (isset($this->y_format[$set]['y_axis']) && $this->y_format[$set]['y_axis'] == 'right') {
                $this->calculated['y_axis_right']['has_data'] = true;
                $dataright = array_merge($dataright, $this->y_data[$set]);
            } else {
                $this->calculated['y_axis_left']['has_data'] = true;
                $dataleft = array_merge($dataleft, $this->y_data[$set]);
            }
        }
        $dataleftrange = $this->find_range($dataleft, $minleft, $maxrleft, $this->parameter['y_resolution_left']);
        $datarightrange = $this->find_range($dataright, $minright, $maxright, $this->parameter['y_resolution_right']);
        $minleft = $dataleftrange['min'];
        $maxrleft = $dataleftrange['max'];
        $minright = $datarightrange['min'];
        $maxright = $datarightrange['max'];

        $this->calculated['y_axis_left']['min']  = $minleft;
        $this->calculated['y_axis_left']['max']  = $maxrleft;
        $this->calculated['y_axis_right']['min'] = $minright;
        $this->calculated['y_axis_right']['max'] = $maxright;

        $stepleft = ($maxrleft - $minleft) / ($this->parameter['y_axis_gridlines'] - 1);
        $startleft = $minleft;
        $stepsright = ($maxright - $minright) / ($this->parameter['y_axis_gridlines'] - 1);
        $startright = $minright;

        if ($this->parameter['y_axis_text_left']) {
            for ($i = 0; $i < $this->parameter['y_axis_gridlines']; $i++) { // Calculate y axis text sizes.
                // Left y axis.
                if ($yticklabels) {
                      $value = $yticklabels[$i];
                } else {
                      $value = number_format($startleft, $this->parameter['y_decimal_left'],
                          $this->parameter['decimal_point'], $this->parameter['thousand_sep']);
                }
                $this->calculated['y_axis_left']['data'][$i]  = $startleft;
                $this->calculated['y_axis_left']['text'][$i]  = $value; // Text is formatted raw data.

                $size = $this->get_boundarybox(
                    array('points' => $axissize,
                          'font'   => $axisfont,
                          'angle'  => $axisangle,
                          'colour' => $axiscolour,
                          'text'   => $value));
                $this->calculated['y_axis_left']['boundary_box'][$i] = $size;

                if ($size['height'] > $this->calculated['y_axis_left']['boundary_box_max']['height']) {
                    $this->calculated['y_axis_left']['boundary_box_max']['height'] = $size['height'];
                }
                if ($size['width'] > $this->calculated['y_axis_left']['boundary_box_max']['width']) {
                    $this->calculated['y_axis_left']['boundary_box_max']['width'] = $size['width'];
                }

                $startleft += $stepleft;
            }
            $this->calculated['boundary_box']['left'] += $this->calculated['y_axis_left']['boundary_box_max']['width']
                                                      + $this->parameter['y_inner_padding'];
        }

        if ($this->parameter['y_axis_text_right']) {
            for ($i = 0; $i < $this->parameter['y_axis_gridlines']; $i++) { // Calculate y axis text sizes.
                // Right y axis.
                $value = number_format($startright, $this->parameter['y_decimal_right'],
                  $this->parameter['decimal_point'], $this->parameter['thousand_sep']);
                $this->calculated['y_axis_right']['data'][$i]  = $startright;
                $this->calculated['y_axis_right']['text'][$i]  = $value; // Text is formatted raw data.
                $size = $this->get_boundarybox(
                    array('points' => $axissize,
                          'font'   => $axisfont,
                          'angle'  => $axisangle,
                          'colour' => $axiscolour,
                          'text'   => $value));
                $this->calculated['y_axis_right']['boundary_box'][$i] = $size;

                if ($size['height'] > $this->calculated['y_axis_right']['boundary_box_max']['height']) {
                    $this->calculated['y_axis_right']['boundary_box_max'] = $size;
                }
                if ($size['width'] > $this->calculated['y_axis_right']['boundary_box_max']['width']) {
                    $this->calculated['y_axis_right']['boundary_box_max']['width'] = $size['width'];
                }

                $startright += $stepsright;
            }
            $this->calculated['boundary_box']['right'] -= $this->calculated['y_axis_right']['boundary_box_max']['width']
                                                      + $this->parameter['y_inner_padding'];
        }
    }

    /**
     * Function to initialize the x-axis.
     *
     */
    public function init_x_axis() {
        $this->calculated['x_axis'] = array(); // Array to hold calculated values for x_axis.
        $this->calculated['x_axis']['boundary_box_max'] = array('height' => 0, 'width' => 0);

        $axisfont       = $this->parameter['axisfont'];
        $axissize       = $this->parameter['axissize'];
        $axiscolour     = $this->parameter['axiscolour'];
        $axisangle      = $this->parameter['x_axisangle'];

        // Check whether to treat x axis as numeric.
        if ($this->parameter['x_axis_gridlines'] == 'auto') { // Auto means text based x_axis, not numeric...
            $this->calculated['x_axis']['num_ticks'] = count($this->x_data);
            $data = $this->x_data;
            for ($i = 0; $i < $this->calculated['x_axis']['num_ticks']; $i++) {
                $value = array_shift($data); // Grab value from begin of array.
                $this->calculated['x_axis']['data'][$i]  = $value;
                $this->calculated['x_axis']['text'][$i]  = $value; // Raw data and text are both the same in this case.
                $size = $this->get_boundarybox(
                    array('points' => $axissize,
                          'font'   => $axisfont,
                          'angle'  => $axisangle,
                          'colour' => $axiscolour,
                          'text'   => $value));
                $this->calculated['x_axis']['boundary_box'][$i] = $size;
                if ($size['height'] > $this->calculated['x_axis']['boundary_box_max']['height']) {
                    $this->calculated['x_axis']['boundary_box_max'] = $size;
                }
            }

        } else { // The x axis is numeric so find max min values...
            $this->calculated['x_axis']['num_ticks'] = $this->parameter['x_axis_gridlines'];

            $min = $this->parameter['x_min'];
            $max = $this->parameter['x_max'];
            $data = array();
            $data = $this->find_range($this->x_data, $min, $max, $this->parameter['x_resolution']);
            $min = $data['min'];
            $max = $data['max'];
            $this->calculated['x_axis']['min'] = $min;
            $this->calculated['x_axis']['max'] = $max;

            $step = ($max - $min) / ($this->calculated['x_axis']['num_ticks'] - 1);
            $start = $min;

            for ($i = 0; $i < $this->calculated['x_axis']['num_ticks']; $i++) { // Calculate x axis text sizes.
                $value = number_format($start, $this->parameter['xDecimal'],
                    $this->parameter['decimal_point'], $this->parameter['thousand_sep']);
                $this->calculated['x_axis']['data'][$i]  = $start;
                $this->calculated['x_axis']['text'][$i]  = $value; // Text is formatted raw data.

                $size = $this->get_boundarybox(
                    array('points' => $axissize,
                          'font'   => $axisfont,
                          'angle'  => $axisangle,
                          'colour' => $axiscolour,
                          'text'   => $value));
                $this->calculated['x_axis']['boundary_box'][$i] = $size;

                if ($size['height'] > $this->calculated['x_axis']['boundary_box_max']['height']) {
                    $this->calculated['x_axis']['boundary_box_max'] = $size;
                }

                $start += $step;
            }
        }
        if ($this->parameter['x_axis_text']) {
            $this->calculated['boundary_box']['bottom'] -= $this->calculated['x_axis']['boundary_box_max']['height']
                                                      + $this->parameter['x_inner_padding'];
        }
    }

    /**
     * Function to find max and min values for a data array given the resolution.
     *
     * @param array $data The array of values for the histogram.
     * @param float $min The minimum value for histogram bars.
     * @param float $max The maximum value for histogram bars.
     * @param float $resolution for creating the histogram.
     * @return array The min and max values.
     */
    public function find_range($data, $min, $max, $resolution) {
        if (count($data) == 0 ) {
            return array('min' => 0, 'max' => 0);
        }
        foreach ($data as $key => $value) {
            if ($value == 'none') {
                continue;
            }
            if ($value > $max) {
                $max = $value;
            }
            if ($value < $min) {
                $min = $value;
            }
        }

        if ($max == 0) {
            $factor = 1;
        } else {
            if ($max < 0) {
                $factor = - pow(10, (floor(log10(abs($max))) + $resolution) );
            } else {
                $factor = pow(10, (floor(log10(abs($max))) - $resolution) );
            }
        }
        if ($factor > 0.1) { // To avoid some wierd rounding errors (Moodle).
            $factor = round($factor * 1000.0) / 1000.0;
        }

        $max = $factor * @ceil($max / $factor);
        $min = $factor * @floor($min / $factor);
        return array('min' => $min, 'max' => $max);
    }

    /**
     * The constructor for this class..
     *
     * @deprecated since Moodle 3.1
     */
    public function __construct() {
        if (func_num_args() == 2) {
            $this->parameter['width']  = func_get_arg(0);
            $this->parameter['height'] = func_get_arg(1);
        }
        $this->calculated['boundary_box'] = array(
            'left'      => 0,
            'top'       => 0,
            'right'     => $this->parameter['width'] - 1,
            'bottom'    => $this->parameter['height'] - 1);

        $this->init_colours();
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function graph() {
        self::__construct();
    }

    /**
     * Prepare label's text for GD output.
     *
     * @param string    $label string to be prepared.
     * @return string   Reversed input string, if we are in RTL mode and has no numbers.
     *                  Otherwise, returns the string as is.
     */
    private function prepare_label_text($label) {
        if (right_to_left() and !preg_match('/[0-9]/i', $label)) {
            return core_text::strrev($label);
        } else {
            return $label;
        }
    }

    /**
     * Functon to print a messae on the histogram. Not usually used.
     *
     * @param string    $message String to be printed.
     */
    public function print_ttf($message) {
        $points    = $message['points'];
        $angle     = $message['angle'];
        // We have to manually reverse the label, since php GD cannot handle RTL characters properly in UTF8 strings.
        $text      = $this->prepare_label_text($message['text']);
        $colour    = $this->colour[$message['colour']];
        $font      = $this->parameter['path_to_fonts'].$message['font'];

        $x         = $message['boundary_box']['x'];
        $y         = $message['boundary_box']['y'];
        $offsetx   = $message['boundary_box']['offsetx'];
        $offsety   = $message['boundary_box']['offsety'];
        $height    = $message['boundary_box']['height'];
        $width     = $message['boundary_box']['width'];
        $reference = $message['boundary_box']['reference'];

        switch ($reference) {
            case 'top-left':
            case 'left-top':
                $y += $height - $offsety;
                $x += $offsetx;
                break;
            case 'left-center':
                $y += ($height / 2) - $offsety;
                $x += $offsetx;
                break;
            case 'left-bottom':
                $y -= $offsety;
                $x += $offsetx;
                break;
            case 'top-center':
                $y += $height - $offsety;
                $x -= ($width / 2) - $offsetx;
                break;
            case 'top-right':
            case 'right-top':
                $y += $height - $offsety;
                $x -= $width - $offsetx;
                break;
            case 'right-center':
                $y += ($height / 2) - $offsety;
                $x -= $width - $offsetx;
                break;
            case 'right-bottom':
                $y -= $offsety;
                $x -= $width - $offsetx;
                break;
            case 'bottom-center':
                $y -= $offsety;
                $x -= ($width / 2) - $offsetx;
                break;
            default:
                $y = 0;
                $x = 0;
                break;
        }
        // Start of Moodle addition.
        $text = core_text::utf8_to_entities($text, true, true); // Does not work with hex entities!
        // End of Moodle addition.
        imagettftext($this->image, $points, $angle, $x, $y, $colour, $font, $text);
    }

    /**
     * Function to move boundarybox to coordinates specified.
     *
     * @param array    $boundarybox The parameters of the box for the histogram.
     * @param array $coords   The coordinates to which the histogram is being moved.
     */
    public function update_boundarybox(&$boundarybox, $coords) {
        $width      = $boundarybox['width'];
        $height     = $boundarybox['height'];
        $x          = $coords['x'];
        $y          = $coords['y'];
        $reference  = $coords['reference'];
        switch ($reference) {
            case 'top-left':
            case 'left-top':
                $top    = $y;
                $bottom = $y + $height;
                $left   = $x;
                $right  = $x + $width;
                break;
            case 'left-center':
                $top    = $y - ($height / 2);
                $bottom = $y + ($height / 2);
                $left   = $x;
                $right  = $x + $width;
                break;
            case 'left-bottom':
                $top    = $y - $height;
                $bottom = $y;
                $left   = $x;
                $right  = $x + $width;
                break;
            case 'top-center':
                $top    = $y;
                $bottom = $y + $height;
                $left   = $x - ($width / 2);
                $right  = $x + ($width / 2);
                break;
            case 'right-top':
            case 'top-right':
                $top    = $y;
                $bottom = $y + $height;
                $left   = $x - $width;
                $right  = $x;
                break;
            case 'right-center':
                $top    = $y - ($height / 2);
                $bottom = $y + ($height / 2);
                $left   = $x - $width;
                $right  = $x;
                break;
            case 'bottom=right':
            case 'right-bottom':
                $top    = $y - $height;
                $bottom = $y;
                $left   = $x - $width;
                $right  = $x;
                break;
            default:
                $top    = 0;
                $bottom = $height;
                $left   = 0;
                $right  = $width;
                break;
        }

        $boundarybox = array_merge($boundarybox, array('top'       => $top,
                                                       'bottom'    => $bottom,
                                                       'left'      => $left,
                                                       'right'     => $right,
                                                       'x'         => $x,
                                                       'y'         => $y,
                                                       'reference' => $reference));
    }

    /**
     * Function return parameters if there is no histogram.
     *
     * @return array The Parameters if there is no histogram.
     */
    public function get_null_size() {
        return array('width'      => 0,
                     'height'     => 0,
                     'offsetx'    => 0,
                     'offsety'    => 0,
                     );
    }

    /**
     * Function to set the proper values for the boundarybox.
     *
     * @param string $message Message that is being displayed.
     * @return array The message and parameters of the box displaying the message.
     */
    public function get_boundarybox($message) {
        $points  = $message['points'];
        $angle   = $message['angle'];
        $font    = $this->parameter['path_to_fonts'].$message['font'];
        $text    = $message['text'];

        // Get font size.
        $bounds = imagettfbbox($points, $angle, $font, "W");
        if ($angle < 0) {
            $fontheight = abs($bounds[7] - $bounds[1]);
        } else if ($angle > 0) {
            $fontheight = abs($bounds[1] - $bounds[7]);
        } else {
            $fontheight = abs($bounds[7] - $bounds[1]);
        }

        // Get boundary box and offsets for printing at an angle.
        // Start of Moodle addition.
        $text = core_text::utf8_to_entities($text, true, true); // Gd does not work with hex entities!
        // End of Moodle addition.
        $bounds = imagettfbbox($points, $angle, $font, $text);

        if ($angle < 0) {
            $width = abs($bounds[4] - $bounds[0]);
            $height = abs($bounds[3] - $bounds[7]);
            $offsety = abs($bounds[3] - $bounds[1]);
            $offsetx = 0;

        } else if ($angle > 0) {
            $width = abs($bounds[2] - $bounds[6]);
            $height = abs($bounds[1] - $bounds[5]);
            $offsety = 0;
            $offsetx = abs($bounds[0] - $bounds[6]);

        } else {
            $width = abs($bounds[4] - $bounds[6]);
            $height = abs($bounds[7] - $bounds[1]);
            $offsety = $bounds[1];
            $offsetx = 0;
        }

        // Return values.
        return array('width'      => $width,
                     'height'     => $height,
                     'offsetx'    => $offsetx,
                     'offsety'    => $offsety,
                     );
    }

    /**
     * Function to draw a rectangle.
     *
     * @param array $border The parameters for the border.
     * @param string $colour The color of the border.
     * @param string $type The type of color to be added to the retangle.
     */
    public function draw_rectangle($border, $colour, $type) {
        $colour = $this->colour[$colour];
        switch ($type) {
            case 'fill':    // Fill the rectangle.
                imagefilledrectangle($this->image, $border['left'], $border['top'], $border['right'], $border['bottom'], $colour);
                break;
            case 'box':     // All sides.
                imagerectangle($this->image, $border['left'], $border['top'], $border['right'], $border['bottom'], $colour);
                break;
            case 'axis':    // Bottom x axis and left y axis.
                imageline($this->image, $border['left'], $border['top'], $border['left'], $border['bottom'], $colour);
                imageline($this->image, $border['left'], $border['bottom'], $border['right'], $border['bottom'], $colour);
                break;
            case 'y':       // Left y axis only.
            case 'y-left':
                imageline($this->image, $border['left'], $border['top'], $border['left'], $border['bottom'], $colour);
                break;
            case 'y-right': // Right y axis only.
                imageline($this->image, $border['right'], $border['top'], $border['right'], $border['bottom'], $colour);
                break;
            case 'x':       // Bottom x axis only.
                imageline($this->image, $border['left'], $border['bottom'], $border['right'], $border['bottom'], $colour);
                break;
            case 'u':       // U shaped. bottom x axis and both left and right y axis.
                imageline($this->image, $border['left'], $border['top'], $border['left'], $border['bottom'], $colour);
                imageline($this->image, $border['right'], $border['top'], $border['right'], $border['bottom'], $colour);
                imageline($this->image, $border['left'], $border['bottom'], $border['right'], $border['bottom'], $colour);
                break;

        }
    }

    /**
     * Function to set the proper colours for the histogram.
     *
     * @return bool Did the colour get set? (Was it found in the list of colours?).
     */
    public function init_colours() {
        $this->image              = imagecreate($this->parameter['width'], $this->parameter['height']);
        // Standard colours.
        $this->colour['white']    = imagecolorallocate ($this->image, 0xFF, 0xFF, 0xFF); // First colour is background colour.
        $this->colour['black']    = imagecolorallocate ($this->image, 0x00, 0x00, 0x00);
        $this->colour['maroon']   = imagecolorallocate ($this->image, 0x80, 0x00, 0x00);
        $this->colour['green']    = imagecolorallocate ($this->image, 0x00, 0x80, 0x00);
        $this->colour['ltgreen']  = imagecolorallocate ($this->image, 0x52, 0xF1, 0x7F);
        $this->colour['ltltgreen'] = imagecolorallocate ($this->image, 0x99, 0xFF, 0x99);
        $this->colour['olive']    = imagecolorallocate ($this->image, 0x80, 0x80, 0x00);
        $this->colour['navy']     = imagecolorallocate ($this->image, 0x00, 0x00, 0x80);
        $this->colour['purple']   = imagecolorallocate ($this->image, 0x80, 0x00, 0x80);
        $this->colour['gray']     = imagecolorallocate ($this->image, 0x80, 0x80, 0x80);
        $this->colour['red']      = imagecolorallocate ($this->image, 0xFF, 0x00, 0x00);
        $this->colour['ltred']    = imagecolorallocate ($this->image, 0xFF, 0x99, 0x99);
        $this->colour['ltltred']  = imagecolorallocate ($this->image, 0xFF, 0xCC, 0xCC);
        $this->colour['orange']   = imagecolorallocate ($this->image, 0xFF, 0x66, 0x00);
        $this->colour['ltorange']   = imagecolorallocate ($this->image, 0xFF, 0x99, 0x66);
        $this->colour['ltltorange'] = imagecolorallocate ($this->image, 0xFF, 0xcc, 0x99);
        $this->colour['lime']     = imagecolorallocate ($this->image, 0x00, 0xFF, 0x00);
        $this->colour['yellow']   = imagecolorallocate ($this->image, 0xFF, 0xFF, 0x00);
        $this->colour['blue']     = imagecolorallocate ($this->image, 0x80, 0x80, 0xFF);
        $this->colour['ltblue']   = imagecolorallocate ($this->image, 0x00, 0xCC, 0xFF);
        $this->colour['ltltblue'] = imagecolorallocate ($this->image, 0x99, 0xFF, 0xFF);
        $this->colour['fuchsia']  = imagecolorallocate ($this->image, 0xFF, 0x00, 0xFF);
        $this->colour['aqua']     = imagecolorallocate ($this->image, 0x00, 0xFF, 0xFF);
        // Shades of gray.
        $this->colour['grayF0']   = imagecolorallocate ($this->image, 0xF0, 0xF0, 0xF0);
        $this->colour['grayEE']   = imagecolorallocate ($this->image, 0xEE, 0xEE, 0xEE);
        $this->colour['grayDD']   = imagecolorallocate ($this->image, 0xDD, 0xDD, 0xDD);
        $this->colour['grayCC']   = imagecolorallocate ($this->image, 0xCC, 0xCC, 0xCC);
        $this->colour['gray33']   = imagecolorallocate ($this->image, 0x33, 0x33, 0x33);
        $this->colour['gray66']   = imagecolorallocate ($this->image, 0x66, 0x66, 0x66);
        $this->colour['gray99']   = imagecolorallocate ($this->image, 0x99, 0x99, 0x99);

        $this->colour['none']   = 'none';
        return true;
    }

    /**
     * Function to output the histogram.
     *
     * @return imgage resource The histogram image.
     */
    public function output() {
        $expiresseconds = $this->parameter['seconds_to_live'];
        $expireshours = $this->parameter['hours_to_live'];

        if ($expireshours || $expiresseconds) {
            $now = mktime (date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"));
            $expires = mktime (date("H") + $expireshours, date("i"), date("s") + $expiresseconds, date("m"), date("d"), date("Y"));
            $expiresgmt = gmdate('D, d M Y H:i:s', $expires).' GMT';
            $lastmodifiedgmt  = gmdate('D, d M Y H:i:s', $now).' GMT';

            header('Last-modified: '.$lastmodifiedgmt);
            header('Expires: '.$expiresgmt);
        }

        if ($this->parameter['file_name'] == 'none') {
            switch ($this->parameter['output_format']) {
                case 'GIF':
                    header("Content-type: image/gif");  // GIF??. switch to PNG guys!!
                    imagegif($this->image);
                    break;
                case 'JPEG':
                    header("Content-type: image/jpeg"); // JPEG for line art??. included for completeness.
                    imagejpeg($this->image);
                    break;
                default:
                    header("Content-type: image/png");  // Preferred output format.
                    imagepng($this->image);
                    break;
            }
        } else {
            switch ($this->parameter['output_format']) {
                case 'GIF':
                    imagegif($this->image, $this->parameter['file_name'].'.gif');
                    break;
                case 'JPEG':
                    imagejpeg($this->image, $this->parameter['file_name'].'.jpg');
                    break;
                default:
                    imagepng($this->image, $this->parameter['file_name'].'.png');
                    break;
            }
        }

        imagedestroy($this->image);
    }

    /**
     * Function to initialize a variable.
     *
     * @param string $variable Variable that is being initialized.
     * @param string $value New value that is being given the variable.
     * @param string $default The value that is given the variable if no value is given.
     */
    public function init_variable(&$variable, $value, $default) {
        if (!empty($value)) {
            $variable = $value;
        } else if (isset($default)) {
            $variable = $default;
        } else {
            unset($variable);
        }
    }

    /**
     * Function to create one of the histogram bars.
     *
     * @param float $x teh x-location of the bar.
     * @param float $y The height of the histogram bar.
     * @param string $type The type of fill for the histogram bar.
     * @param float $size The size of the histogram bar.
     * @param string $colour The colour of the histrogram bar.
     * @param float $offset The size of the shadow for the bar.
     * @param int $index The index for the shadowing.
     * @param float $yoffset The y offset for the shadowing.
     */
    public function bar($x, $y, $type, $size, $colour, $offset, $index, $yoffset) {
        $indexoffset = $this->calculated['bar_offset_index'][$index];
        if ( $yoffset ) {
            $baroffsetx = 0;
        } else {
            $baroffsetx = $this->calculated['bar_offset_x'][$indexoffset];
        }

        $span = ($this->calculated['bar_width'] * $size) / 2;
        $xleft  = $x + $baroffsetx - $span;
        $xright = $x + $baroffsetx + $span;

        if ($this->parameter['zero_axis'] != 'none') {
            $zero = $this->calculated['zero_axis'];
            if ($this->parameter['shadow_below_axis'] ) {
                $zero  += $offset;
            }
            $uleft  = $xleft + $offset;
            $uright = $xright + $offset - 1;
            $v       = $this->calculated['boundary_box']['bottom'] - $y + $offset;

            if ($v > $zero) {
                $top = $zero + 1;
                $bottom = $v;
            } else {
                $top = $v;
                $bottom = $zero - 1;
            }

            switch ($type) {
                case 'open':
                    if ($v > $zero) {
                        imagerectangle($this->image, round($uleft), $bottom, round($uright), $bottom, $this->colour[$colour]);
                    } else {
                        imagerectangle($this->image, round($uleft), $top, round($uright), $top, $this->colour[$colour]);
                    }
                    imagerectangle($this->image, round($uleft), $top, round($uleft), $bottom, $this->colour[$colour]);
                    imagerectangle($this->image, round($uright), $top, round($uright), $bottom, $this->colour[$colour]);
                    break;
                case 'fill':
                    imagefilledrectangle($this->image, round($uleft), $top, round($uright), $bottom, $this->colour[$colour]);
                    break;
            }

        } else {

            $bottom = $this->calculated['boundary_box']['bottom'];
            if ($this->parameter['shadow_below_axis'] ) {
                $bottom  += $offset;
            }
            if ($this->parameter['inner_border'] != 'none') {
                $bottom -= 1;
            }          // One pixel above bottom if border is to be drawn.
            $uleft  = $xleft + $offset;
            $uright = $xright + $offset - 1;
            $v       = $this->calculated['boundary_box']['bottom'] - $y + $offset;

            // Moodle. addition, plus the function parameter yoffset.
            if ($yoffset) {                                           // Moodle.
                $yoffset = $yoffset - round(($bottom - $v) / 2.0);    // Moodle..
                $bottom -= $yoffset;                                  // Moodle.
                $v      -= $yoffset;                                  // Moodle.
            }                                                         // Moodle.

            switch ($type) {
                case 'open':
                    imagerectangle($this->image, round($uleft), $v, round($uright), $bottom, $this->colour[$colour]);
                    break;
                case 'fill':
                    imagefilledrectangle($this->image, round($uleft), $v, round($uright), $bottom, $this->colour[$colour]);
                    break;
            }
        }
    }


}
