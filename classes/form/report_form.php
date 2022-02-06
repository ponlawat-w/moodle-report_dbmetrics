<?php
namespace report_dbmetrics\form;

use html_writer;

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/../../../../lib/formslib.php');
require_once(__DIR__ . '/../../lib.php');

class filter_form extends \moodleform
{
    private $courseid;

    public function __construct($courseid)
    {
        $this->courseid = $courseid;
        parent::__construct();
    }

    public function definition()
    {
        $mform = $this->_form;

        $type = [
            REPORT_DBMETRICS_TYPE_INDIVIDUAL => get_string('type_individual', 'report_dbmetrics'),
            REPORT_DBMETRICS_TYPE_GROUP => get_string('type_group', 'report_dbmetrics'),
            REPORT_DBMETRICS_TYPE_MODULE => get_string('type_module', 'report_dbmetrics')
        ];
        $mform->addElement('select', 'type', get_string('type', 'report_dbmetrics'), $type);
        $mform->setDefault('type', REPORT_DBMETRICS_TYPE_INDIVIDUAL);
        $mform->setType('type', PARAM_INT);

        $datamodules = report_dbmetrics_getmoduleoptions($this->courseid);
        $mform->addElement('static', 'modulesstatic', get_string('modules', 'report_dbmetrics'), get_string('modulestoinclude', 'report_dbmetrics'));
        foreach ($datamodules as $dataid => $dataname) {
            $elementname = "modules[{$dataid}]";
            $mform->addElement('advcheckbox', $elementname, '', $dataname, [], [null, $dataid]);
            $mform->setDefault($elementname, true);
        }

        $groups = report_dbmetrics_getgroupoptions($this->courseid);
        $mform->addElement('select', 'groups', get_string('groups', 'report_dbmetrics'), $groups)->setMultiple(true);
        $mform->setDefault('groups', array_keys($groups));

        $mform->addElement('date_time_selector', 'starttime', get_string('startdate', 'report_dbmetrics'), ['optional' => true, 'startyear' => 2000, 'stopyear' => date('Y'), 'step' => 5]);
        $mform->addElement('date_time_selector', 'endtime', get_string('enddate', 'report_dbmetrics'), ['optional' => true, 'startyear' => 2000, 'stopyear' => date('Y'), 'step' => 5]);

        $this->definewordcountfields($datamodules);

        $formats = \core_plugin_manager::instance()->get_plugins_of_type('dataformat');
        $formatoptions = [REPORT_DBMETRICS_FORMAT_TABLE => get_string('format_table', 'report_dbmetrics')];
        foreach ($formats as $format) {
            $formatoptions[$format->name] = $format->displayname;
        }
        $mform->addElement('select', 'format', get_string('outputformat', 'report_dbmetrics'), $formatoptions);
        $mform->setDefault('format', REPORT_DBMETRICS_FORMAT_TABLE);

        $mform->addElement('hidden', 'id', $this->courseid);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('proceed', 'report_dbmetrics'));
    }

    private function definewordcountfields($datamoduleoptions)
    {
        global $DB;
        $mform = $this->_form;
        foreach ($datamoduleoptions as $dataid => $dataname) {
            $fields = $DB->get_records('data_fields', ['dataid' => $dataid]);
            $options = [];
            $defaults = [];
            foreach ($fields as $field) {
                $options[$field->id] = "{$field->name} ({$field->type})";
                if ($field->type == 'text' || $field->type == 'textarea') {
                    $defaults[] = $field->id;
                }
            }
            $elementname = "wordcountfields[{$dataid}]";
            $mform->addElement('select', $elementname, get_string('wordcountfield', 'report_dbmetrics', $dataname), $options)->setMultiple(true);
            $mform->setDefault($elementname, $defaults);
            $mform->hideIf($elementname, "modules[{$dataid}]", 'notchecked');
        }
    }
}
