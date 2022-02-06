<?php

defined('MOODLE_INTERNAL') or die();

const REPORT_DBMETRICS_TYPE_INDIVIDUAL = 1;
const REPORT_DBMETRICS_TYPE_GROUP = 2;
const REPORT_DBMETRICS_TYPE_MODULE = 3;

const REPORT_DBMETRICS_FORMAT_TABLE = '0_table';

function report_dbmetrics_extend_navigation_course($navigation, $course, $context) {
    if (!has_capability('report/dbmetrics:view', $context)) {
        return;
    }
    $url = new moodle_url('/report/dbmetrics/index.php', ['id' => $course->id]);
    $navigation->add(get_string('dbmetrics', 'report_dbmetrics'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

function report_dbmetrics_getmoduleoptions($courseid) {
    global $DB;
    $datamodules =  $DB->get_records('data', ['course' => $courseid]);
    $results = [];
    foreach ($datamodules as $datamodule) {
        $results[$datamodule->id] = $datamodule->name;
    }
    return $results;
}

function report_dbmetrics_getgroupoptions($courseid) {
    $groups = groups_get_all_groups($courseid);
    $results = [];
    foreach ($groups as $group) {
        $results[$group->id] = $group->name;
    }
    return $results;
}

function report_dbmetrics_usergetgroupids($userid, $courseid) {
    $usergroups = groups_get_user_groups($courseid, $userid);
    $results = [];
    foreach ($usergroups[0] as $groupid) {
        $results[] = $groupid;
    }
    return $results;
}

function report_dbmetrics_usergetgroups($userid, $courseid) {
    return array_map(function($groupid) { return groups_get_group($groupid); }, report_dbmetrics_usergetgroupids($userid, $courseid));
}
