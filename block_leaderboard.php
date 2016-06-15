<?php
// This file is part of leaderboard block for Moodle - http://moodle.org/
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
 * leaderboard block definition
 *
 * @package    contrib
 * @subpackage block_ranking -> changed to block_leaderboard by Kiya Govek
 * @copyright  2015 Willian Mano http://willianmano.net
 * @authors    Willian Mano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/leaderboard/lib.php');

class block_leaderboard extends block_base {

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('leaderboard', 'block_leaderboard');
    }

    /**
     * Controls the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
        $title = isset($this->config->leaderboard_title) ? trim($this->config->leaderboard_title) : '';
        if (!empty($title)) {
            $this->title = format_string($this->config->leaderboard_title);
        }
    }

    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view'    => true,
            'site'           => false,
            'mod'            => false,
            'my'             => false
        );
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $leaderboardsize = isset($this->config->leaderboard_leaderboardsize) ? trim($this->config->leaderboard_leaderboardsize) : 0;

//         $weekstart = strtotime(date('d-m-Y', strtotime('-'.date('w').' days')));
//         $leaderboardlastweek = block_leaderboard_get_students_by_date($leaderboardsize, $weekstart, time());
// 
//         $monthstart = strtotime(date('Y-m-01'));
//         $leaderboardlastmonth = block_leaderboard_get_students_by_date($leaderboardsize, $monthstart, time());
// 
//         $leaderboardgeral = block_leaderboard_get_groups($leaderboardsize);
// 
//         $leaderboardstables = block_leaderboard_print_students($leaderboardlastmonth, $leaderboardlastweek, $leaderboardgeral);

        $leaderboardstudents = block_leaderboard_get_students($leaderboardsize);
        
        $leaderboardgroups = block_leaderboard_get_groups($this->config);
        
        $leaderboardstables = block_leaderboard_print_tables($leaderboardgroups, $leaderboardstudents);

        $individualleaderboard = block_leaderboard_print_individual_leaderboard();

        $this->content->text = $leaderboardstables . $individualleaderboard;

        $this->content->footer .= html_writer::tag('p',
                                        html_writer::link(
                                            new moodle_url(
                                                '/blocks/leaderboard/report.php',
                                                array('courseid' => $this->page->course->id)
                                            ),
                                            get_string('see_full_leaderboard', 'block_leaderboard'),
                                            array('class' => 'btn btn-default')
                                        )
                                  );

        return $this->content;
    }

    /**
     * Allow block instance configuration
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }
}