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
 * leaderboard block configuration form definition
 *
 * @package    contrib
 * @subpackage block_ranking -> changed to block_leaderboard by Kiya Govek
 * @copyright  2015 Willian Mano http://willianmano.net
 * @authors    Willian Mano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class block_leaderboard_edit_form extends block_edit_form {

    public function specific_definition($mform) {
        global $CFG, $DB, $COURSE;

        $mform->addElement('header', 'displayinfo', get_string('configuration', 'block_leaderboard'));

        $mform->addElement('text', 'config_leaderboard_title', get_string('blocktitle', 'block_leaderboard'));
        $mform->setDefault('config_leaderboard_title', get_string('leaderboard', 'block_leaderboard'));
        $mform->addRule('config_leaderboard_title', null, 'required', null, 'client');
        $mform->setType('config_title', PARAM_MULTILANG);

        $mform->addElement('text', 'config_leaderboard_leaderboardsize', get_string('leaderboardsize', 'block_leaderboard'));
        $mform->setDefault('config_leaderboard_leaderboardsize', get_config('block_leaderboard','leaderboardsize'));
        $mform->setType('config_leaderboard_leaderboardsize', PARAM_INT);
        
        // select which grouping to show
        $sql = "SELECT gr.name AS name
            FROM {groupings} as gr
            WHERE gr.courseid = :courseid";
        $params['courseid'] = $COURSE->id;
        $groupings = $DB->get_records_sql($sql, $params);
        $groupings_list = array();
        foreach ($groupings as $gr) {
            $groupings_list[] = $gr->name;
        }
        
        $mform->addElement('select', 'config_leaderboard_displaygrouping', 
            get_string('config_leaderboard_displaygrouping', 'block_leaderboard'), $groupings_list);
        $mform->addHelpButton('config_leaderboard_displaygrouping', 'config_leaderboard_displaygrouping', 'block_leaderboard');
        
        
        
    }
}