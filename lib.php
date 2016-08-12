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

define ('DEFAULT_POINTS', 2);

// Store the courses contexts.
$coursescontexts = array();

/**
 * Return the list of students in the course leaderboard
 *
 * @param int
 * @return mixed
 */
function block_leaderboard_get_students($limit = null) {
    global $COURSE, $DB, $PAGE;

    // Get block leaderboard configuration.
    $cfgleaderboard = get_config('block_leaderboard');

    // Get limit from default configuration or instance configuration.
    if (!$limit) {
        if (isset($cfgleaderboard->leaderboardsize) && trim($cfgleaderboard->leaderboardsize) != '') {
            $limit = $cfgleaderboard->leaderboardsize;
        } else {
            $limit = 5;
        }
    }

    $context = $PAGE->context;

    $userfields = user_picture::fields('u', array('username'));

    // Changed SQL query to count badges awarded - Kiya Govek 4/16
    $sql = "SELECT $userfields,
            b.name as badgename,
            bi.badgeid as badgeid,
            c.id as contextid,
            COUNT(bi.badgeid) as points
        FROM {user} u
        LEFT JOIN {badge_issued} bi ON bi.userid = u.id
        LEFT JOIN {badge} b ON b.id = bi.badgeid
        INNER JOIN {role_assignments} a ON a.userid = u.id
        INNER JOIN {context} c ON c.id = a.contextid
        WHERE b.courseid = :courseid AND a.roleid = :roleid AND c.id = :contextid
        GROUP BY u.id
        ORDER BY points DESC
        LIMIT " . $limit;
    $params['contextid'] = $context->id;
    $params['roleid'] = 5;
    $params['courseid'] = $COURSE->id;

    $users = array_values($DB->get_records_sql($sql, $params));

    return $users;
}

function block_leaderboard_print_tables($leaderboardgroups, $leaderboardstudents, $grouptabset, $individualtabset) {
    global $PAGE;
    
    $tablegroups = generate_group_table($leaderboardgroups);
    $tablestudents = generate_table($leaderboardstudents);
    
    $PAGE->requires->js_init_call('M.block_leaderboard.init_tabview');
    
    if ($grouptabset && $individualtabset) {
        return '<div id="leaderboard-tabs">
                <ul>
                    <li><a href="#groups">'.get_string('groups', 'block_leaderboard').'</a></li>
                    <li><a href="#individual">'.get_string('individual', 'block_leaderboard').'</a></li>
                </ul>
                <div>
                    <div id="groups">'.$tablegroups.'</div>
                    <div id="individual">'.$tablestudents.'</div>
                </div>
            </div>';
    } else if ($individualtabset) {
        return '<div id="leaderboard-tabs">
                <ul>
                    <li><a href="#individual">'.get_string('individual', 'block_leaderboard').'</a></li>
                </ul>
                <div>
                    <div id="individual">'.$tablestudents.'</div>
                </div>
            </div>';
    } else {
        return '<div id="leaderboard-tabs">
                <ul>
                    <li><a href="#groups">'.get_string('groups', 'block_leaderboard').'</a></li>
                </ul>
                <div>
                    <div id="groups">'.$tablegroups.'</div>
                </div>
            </div>';
    }
}

/**
 * Print the student individual leaderboard points
 *
 * @return string
 */
function block_leaderboard_print_individual_leaderboard() {
    global $USER, $COURSE;

    if (!is_student($USER->id)) {
        return '';
    }

    $totalpoints = block_leaderboard_get_student_points($USER->id);
    $totalpoints = $totalpoints->points != null ? $totalpoints->points : '0';
    $totalpoints = $totalpoints . " " . strtolower(get_string('table_points', 'block_leaderboard'));

    return "<h5>".get_string('your_score', 'block_leaderboard').": ".$totalpoints."</h5>";
}

/**
 * Get the student points
 *
 * @param int
 * @return mixed
 */
function block_leaderboard_get_student_points($userid) {
    global $COURSE, $DB;

    $sql = "SELECT COUNT(bi.badgeid) as points
        FROM {user} u
        LEFT JOIN {badge_issued} bi ON bi.userid = u.id
        LEFT JOIN {badge} b ON b.id = bi.badgeid
        WHERE u.id = :userid AND b.courseid = :courseid";
    $params['userid'] = $userid;
    $params['courseid'] = $COURSE->id;
    
    return $DB->get_record_sql($sql,$params);
}

/**
 * Return a table of leaderboard based on data passed
 *
 * @param mixed
 * @return mixed
 */
function generate_table($data) {
    global $USER, $OUTPUT, $PAGE;

    if (empty($data)) {
        return get_string('nostudents', 'block_leaderboard');
    }

    $table = new html_table();
    $table->attributes = array("class" => "leaderboardTable table table-striped generaltable");
    $table->head = array(
                        get_string('table_position', 'block_leaderboard'),
                        get_string('table_name', 'block_leaderboard'),
                        get_string('table_points', 'block_leaderboard')
                    );
    $lastpos = 1;
    $lastpoints = current($data)->points;
    for ($i = 0; $i < count($data); $i++) {
        $row = new html_table_row();

        // Verify if the logged user is one user in leaderboard.
        if ($data[$i]->id == $USER->id) {
            $row->attributes = array('class' => 'itsme');
        }

        if ($lastpoints > $data[$i]->points) {
            $lastpos++;
            $lastpoints = $data[$i]->points;
        }
        
        $user_contextid = get_user_contextid($data[$i]->id);
        
        $userpictureurl = moodle_url::make_pluginfile_url($user_contextid, 'user', 'icon', $PAGE->theme->name, '/', 'f2');
        $userpictureurl->param('rev', $data[$i]->picture);
                    
        $row->cells = array(
                $lastpos,
                $data[$i]->picture ?'<img class="userpicture" src="'.$userpictureurl.'"'.
                ' alt="Picture of '.$data[$i]->firstname.' '.$data[$i]->lastname.
                '" title="Picture of'.$data[$i]->firstname.' '.$data[$i]->lastname.
                '"/>'.
                ' '.$data[$i]->firstname : $data[$i]->firstname,
                $data[$i]->points
            );
            
        $table->data[] = $row;
    }

    return html_writer::table($table);
}

/**
* Kiya Govek
* Return a table of leaderboard based on group data passed
*
* @param mixed
* @return mixed
*/
function generate_group_table($data) {
    global $USER, $OUTPUT;

    if (empty($data)) {
        return get_string('nogroups', 'block_leaderboard');
    }
    $table = new html_table();
    $table->attributes = array("class" => "leaderboardTable table table-striped generaltable");
    $table->head = array(
                        get_string('table_position', 'block_leaderboard'),
                        get_string('table_name', 'block_leaderboard'),
                        get_string('table_points', 'block_leaderboard')
                    );
    
    $lastpos = 0;
    $num_users = num_users_group(current($data)->groupid);
    $copy_data = $data;
    $length_data = count($data);
    $sort_index = 0;
    while ($sort_index < $length_data) {
        // Find highest data value (for selection sort)
        $i = $sort_index;
        $temp_i = $sort_index;
        $highest_value = 0;
        while ($temp_i < $length_data) {
            $num_users = num_users_group($copy_data[$temp_i]->groupid);
            $points = $copy_data[$temp_i]->badges / $num_users;
            $points = number_format((float)$points, 2, '.', ' ');
            if ($points > $highest_value) {
                $highest_value = $points;
                $i = $temp_i;
            }
            $temp_i++;
        }
        
    
        $row = new html_table_row();    

        // Verify if the logged user is one user in leaderboard.
        if (is_user_group($copy_data[$i]->groupid, $USER->id)) {
            $row->attributes = array('class' => 'itsme');
        }

        $num_users = num_users_group($copy_data[$i]->groupid);
        $points = $copy_data[$i]->badges / $num_users;
        $points = number_format((float)$points, 2, '.', ' ');
        
        
        $grouppictureurl = moodle_url::make_pluginfile_url($copy_data[$i]->contextid, 'group', 'icon', $copy_data[$i]->groupid, '/', 'f2');
        $grouppictureurl->param('rev', $copy_data[$i]->grouppicture);

        $row->cells = array(
                        $lastpos,
                        $copy_data[$i]->grouppicture ?'<img class="grouppicture" src="'.$grouppictureurl.'"'.
                        ' alt="'.$copy_data[$i]->groupname.'" title="'.$copy_data[$i]->groupname.
                        '" width="20" height="20"/>'.
                        ' '.$copy_data[$i]->groupname : $copy_data[$i]->groupname,
                        $points
                    );     

        $table->data[] = $row;
        $temp_holder = $copy_data[$i];
        $copy_data[$i] = $copy_data[$sort_index];
        $copy_data[$sort_index] = $temp_holder;
        $sort_index++;
    }
    
    $last_pos = 1;
    for ($i=0; $i < count($table->data); $i++) {
        if ($i==0) {
            $table->data[$i]->cells[0] = $last_pos;
            continue;
        }
        $prev_points = $table->data[$i-1]->cells[2];
        $points = $table->data[$i]->cells[2];
        if ($prev_points != $points) {
            $last_pos++;
            $table->data[$i]->cells[0] = $last_pos;
        } else {
            $table->data[$i]->cells[0] = $last_pos;
        }
    }

    return html_writer::table($table);
}

/**
 * Verify if the user is a student
 *
 * @param int
 * @param int
 * @return bool
 */
function is_student($userid) {
    return user_has_role_assignment($userid, 5);
}

/**
* Kiya Govek
* Get list of groups ordered by number of badges
*
* @param stdClass config info
* @return mixed
*/
function block_leaderboard_get_groups($config) {
    global $COURSE, $DB, $PAGE;

    $context = $PAGE->context;

    // Changed SQL query to count badges awarded to groups - Kiya Govek 5/16
	$sql = "SELECT g.name as groupname,
	        g.id as groupid,
	        g.picture as grouppicture,
	        c.id as contextid,
		    COUNT(badgetable.badgeid) as badges
        FROM {groups} g
        LEFT JOIN {groupings_groups} gg ON gg.groupid = g.id
        LEFT JOIN {groupings} gr ON gg.groupingid = gr.id
		LEFT JOIN {groups_members} gm ON gm.groupid = g.id
		LEFT JOIN {user} u ON gm.userid = u.id
        LEFT JOIN 
        (SELECT b.id AS badgeid, b.courseid AS courseid, bi.userid AS badgeuserid
        FROM {badge_issued} bi
        LEFT JOIN {badge} b ON b.id = bi.badgeid WHERE courseid = :badgecourseid) AS badgetable
        ON badgeuserid = u.id
        INNER JOIN {role_assignments} ra ON ra.userid = u.id
        INNER JOIN {context} c ON c.id = ra.contextid
        WHERE ra.roleid = 5 AND c.instanceid = :courseid AND c.contextlevel = 50 AND gr.id = :groupingid
		GROUP BY g.id";
    $params['courseid'] = $COURSE->id;
    $params['badgecourseid'] = $COURSE->id;
    if (isset($config->leaderboard_displaygrouping)) {
        $groupings_list = get_groupings();
        $groupings_index = $config->leaderboard_displaygrouping;
        $params['groupingid'] = $groupings_list[$groupings_index]->id;
    } else {
        $params['groupingid'] = 0;
    }

    $groups = array_values($DB->get_records_sql($sql, $params));

    return $groups;
}

/**
* Kiya Govek
* Check number of users in a group
*
* @param int
* @param int
*/
function num_users_group($groupid) {
    global $COURSE, $DB;
    $sql = "SELECT COUNT(u.id) as users
            FROM {groups} g
            LEFT JOIN {groups_members} gm ON gm.groupid = g.id
            LEFT JOIN {user} u ON gm.userid = u.id
            WHERE g.id = :groupid";
    $params['groupid'] = $groupid;
    $db_return = array_values($DB->get_records_sql($sql, $params));
    return $db_return[0]->users;
}
    
/**
* Kiya Govek
* Get list of groups a user is in
*
* @param int
* @return mixed
*/
function get_user_groups($userid) {
    global $COURSE, $DB;
    $sql = "SELECT g.name as groupname,
            g.id as groupid
    FROM {groups} g
	LEFT JOIN {groups_members} gm ON gm.groupid = g.id
	LEFT JOIN {user} u ON gm.userid = u.id
	WHERE u.id = :userid AND g.courseid = :courseid";
	$params['userid'] = $userid;
	$params['courseid'] = $COURSE->id;
	
	return array_values($DB->get_records_sql($sql, $params));
}

/**
* Kiya Govek
* Check if group is in list of groups for user
* 
* @param int
* @return bool
*/
function is_user_group($groupid, $userid) {
    $user_groups = get_user_groups($userid);
    if (empty($user_groups)) {
        return False;
    }
    for ($i = 0; $i < count($user_groups); $i++) {
        if ($groupid == $user_groups[$i]->groupid) {
            return True;
        }
    }
    return False;
}

/**
* Kiya Govek
* Get list of groupings in a course
* 
* @return mixed
*/
function get_groupings() {
    global $COURSE, $DB;
    $sql = "SELECT gr.name AS name,
            gr.id AS id
            FROM {groupings} as gr
            WHERE gr.courseid = :courseid";
    $params['courseid'] = $COURSE->id;
    return array_values($DB->get_records_sql($sql, $params));
}

/**
* Kiya Govek
* Get user context id
*
* @return int
*/
function get_user_contextid($userid) {
    global $DB;
    $sql = "SELECT c.id AS contextid
            FROM {user} u 
            LEFT JOIN {context} c ON c.instanceid = u.id
            WHERE c.contextlevel = 30 AND u.id = :userid";
    $params['userid'] = $userid;
    return array_values($DB->get_records_sql($sql,$params)) ? array_values($DB->get_records_sql($sql,$params))[0]->contextid : 0;
}