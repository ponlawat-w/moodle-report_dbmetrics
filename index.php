<?php

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);

$coursecontext = context_course::instance($courseid);
require_login($courseid);
require_capability('report/dbmetrics:view', $coursecontext);

require_once(__DIR__ . '/classes/report.php');
require_once(__DIR__ . '/classes/form/report_form.php');

$course = get_course($courseid);
$form = new \report_dbmetrics\form\filter_form($courseid);

if ($form->is_submitted() && $form->is_cancelled())
{
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
    exit;
}

$resulttable = null;
if ($form->is_submitted() && $form->is_validated())
{
    $formdata = $form->get_data();
    $report = new \report_dbmetrics\report($formdata);
    if ($formdata->format != REPORT_DBMETRICS_FORMAT_TABLE) {
        $report->download();
        exit;
    }
    $resulttable = $report->gettable();
}

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new moodle_url('/report/dbmetrics/index.php', ['id' => $courseid]));
$PAGE->set_title(get_string('pagetitle', 'report_dbmetrics', ['courseshortname' => $course->shortname]));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
$form->display();
if ($resulttable) {
    echo html_writer::start_tag('hr');
    echo html_writer::table($resulttable);
}
echo $OUTPUT->footer();
