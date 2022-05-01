<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Function libraries
 * 
 * @package report_dbmetrics
 * @copyright 2022 Ponlawat Weerapanpisit
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die();

const REPORT_DBMETRICS_TYPE_INDIVIDUAL = 1;
const REPORT_DBMETRICS_TYPE_GROUP = 2;
const REPORT_DBMETRICS_TYPE_MODULE = 3;

const REPORT_DBMETRICS_FORMAT_TABLE = '0_table';

/**
 * Extending course module menu by adding an item to access the plugin in the database module
 *
 * @param mixed $navigation
 * @param object $course
 * @param context $context
 * @return void
 */
function report_dbmetrics_extend_navigation_course($navigation, $course, $context) {
    if (!has_capability('report/dbmetrics:view', $context)) {
        return;
    }
    $url = new moodle_url('/report/dbmetrics/index.php', ['id' => $course->id]);
    $navigation->add(get_string('dbmetrics', 'report_dbmetrics'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

/**
 * Get dropdown options of existing database modules in a course
 *
 * @param int $courseid
 * @return string[] An array of database module names in values with keys being IDs
 */
function report_dbmetrics_getmoduleoptions($courseid) {
    global $DB;
    $datamodules =  $DB->get_records('data', ['course' => $courseid]);
    $results = [];
    foreach ($datamodules as $datamodule) {
        $results[$datamodule->id] = $datamodule->name;
    }
    return $results;
}

/**
 * Get gropdown options of existing groups in a course
 *
 * @param int $courseid
 * @return string[] An array of group names in values with keys being group IDs
 */
function report_dbmetrics_getgroupoptions($courseid) {
    $groups = groups_get_all_groups($courseid);
    $results = [];
    foreach ($groups as $group) {
        $results[$group->id] = $group->name;
    }
    return $results;
}

/**
 * Get IDs of all groups of a user in a course
 *
 * @param int $userid
 * @param int $courseid
 * @return int[]
 */
function report_dbmetrics_usergetgroupids($userid, $courseid) {
    $usergroups = groups_get_user_groups($courseid, $userid);
    $results = [];
    foreach ($usergroups[0] as $groupid) {
        $results[] = $groupid;
    }
    return $results;
}

/**
 * Get group objects of all groups of a user in a course
 *
 * @param int $userid
 * @param int $courseid
 * @return object[]
 */
function report_dbmetrics_usergetgroups($userid, $courseid) {
    return array_map(function($groupid) { return groups_get_group($groupid); }, report_dbmetrics_usergetgroupids($userid, $courseid));
}
