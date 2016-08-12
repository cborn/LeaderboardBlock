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
 * @authors    Willian Mano, edits by Kiya Govek
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

        $leaderboardstudents = block_leaderboard_get_students($leaderboardsize);
        
        $leaderboardgroups = block_leaderboard_get_groups($this->config);
        
        $grouptabset = isset($this->config->leaderboard_grouptab) ? $this->config->leaderboard_grouptab : 0;
        $individualtabset = isset($this->config->leaderboard_grouptab) ? $this->config->leaderboard_individualtab : 0;
        
        $leaderboardstables = block_leaderboard_print_tables($leaderboardgroups, $leaderboardstudents,
                            $grouptabset,$individualtabset);

        $individualleaderboard = block_leaderboard_print_individual_leaderboard();

        $this->content->text = $leaderboardstables . $individualleaderboard;
        
        //adds full ranking button
//         $this->content->footer .= html_writer::tag('p',
//                                         html_writer::link(
//                                             new moodle_url(
//                                                 '/blocks/leaderboard/report.php',
//                                                 array('courseid' => $this->page->course->id)
//                                             ),
//                                             get_string('see_full_leaderboard', 'block_leaderboard'),
//                                             array('class' => 'btn btn-default')
//                                         )
//                                   );

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