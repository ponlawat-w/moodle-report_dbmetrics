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

namespace report_dbmetrics;

use coding_exception;
use comment_manager;
use context_module;
use html_table;
use moodle_exception;

require_once(__DIR__ . '/resultrecord.php');
require_once(__DIR__ . '/../../../mod/data/lib.php');

/**
 * A report instance
 * 
 * @package report_dbmetrics
 * @copyright 2022 Ponlawat Weerapanpisit
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report
{
    private $_formdata;
    private $_courseid;
    private $_coursemodules;
    private $_coursemodulecontexts;

    /**
     * Constructor
     *
     * @param object $formdata
     */
    public function __construct($formdata)
    {
        $this->_formdata = $formdata;
        $this->_formdata->modules = array_filter($this->_formdata->modules, function ($dataid) { return trim($dataid) ? true : false; } );
        $this->_courseid = $formdata->id;
        $this->_coursemodules = [];
        foreach ($this->_formdata->modules as $dataid) {
            $this->_coursemodules[$dataid] = get_coursemodule_from_instance('data', $dataid);
            $this->_coursemodulecontexts[$dataid] = context_module::instance($this->_coursemodules[$dataid]->id);
        }
    }

    /**
     * Get report results
     *
     * @return \report_dbmetrics\resultrecord[] Array of result records, with key being the ID of depending on report type
     */
    public function getresults()
    {
        $results = $this->prepareresults();
        $datarecords = $this->getdatarecords();
        foreach ($datarecords as $datarecord) {
            $keys = $this->gettargetresultrecordkeys($datarecord);
            self::sumtargetresultrecord($results, $keys, 'entries', 1);

            if (isset($this->_formdata->wordcountfields) && isset($this->_formdata->wordcountfields[$datarecord->dataid])) {
                $wordcountfields = $this->_formdata->wordcountfields[$datarecord->dataid];
                if (count($wordcountfields)) foreach($wordcountfields as $wordcountfieldid) {
                    self::sumtargetresultrecord($results, $keys, 'words', $this->countwords($datarecord, $wordcountfieldid));
                }
            }

            $this->processcommentscount($results, $keys, $datarecord);
        }
        return $results;
    }

    /**
     * Count words of the field in the specified database record
     *
     * @param object $datarecord
     * @param int $fieldid
     * @return int
     */
    private function countwords($datarecord, $fieldid)
    {
        global $DB;
        $fieldrecord = $DB->get_record('data_fields', ['id' => $fieldid]);
        $fieldobj = data_get_field($fieldrecord, $datarecord, $this->_coursemodules[$datarecord->dataid]);
        $content = $DB->get_record('data_content', ['fieldid' => $fieldid, 'recordid' => $datarecord->id]);
        $contentvalue = $fieldobj->get_content_value($content);
        return str_word_count($contentvalue);
    }

    /**
     * Count comments of a database record
     *
     * @param \report_dbmetrics\resultrecord[] $results
     * @param int[] $keys
     * @param object $datarecord
     * @return void
     */
    private function processcommentscount(&$results, $keys, $datarecord)
    {
        global $DB;
        $comments = $DB->get_records('comments', ['contextid' => $this->_coursemodulecontexts[$datarecord->dataid]->id, 'itemid' => $datarecord->id]);

        if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_INDIVIDUAL) {
            self::sumtargetresultrecord($results, $keys, 'commentsreceived', count($comments));
            foreach ($comments as $comment) {
                self::sumtargetresultrecord($results, [$comment->userid], 'commentsmade', 1);
            }
        } else if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_GROUP) {
            self::sumtargetresultrecord($results, $keys, 'commentsreceived', count($comments));
            foreach ($comments as $comment) {
                $groupids = report_dbmetrics_usergetgroupids($comment->userid, $this->_courseid);
                foreach ($groupids as $groupid) {
                    self::sumtargetresultrecord($results, [$groupid], 'commentsmade', 1);
                }
            }
        } else if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_MODULE) {
            self::sumtargetresultrecord($results, $keys, 'totalcomments', count($comments));
        } else {
            throw new moodle_exception('Unknown filter type');
        }
    }

    /**
     * Get all database records aggregated
     * 
     * @return object[]
     */
    private function getdatarecords()
    {
        global $DB;

        if (!count($this->_formdata->modules)) {
            return [];
        }

        $params = [];

        list($modulesinsql, $modulesinparam) = $DB->get_in_or_equal($this->_formdata->modules, SQL_PARAMS_NAMED);

        $params += $modulesinparam;
        $params['courseid'] = $this->_courseid;

        $conditions = [
            'd.course = :courseid',
            'r.dataid ' . $modulesinsql
        ];

        if ($this->_formdata->starttime) {
            $conditions[] = 'r.timecreated > :starttime';
            $params['starttime'] = $this->_formdata->starttime;
        }
        if ($this->_formdata->endtime) {
            $conditions[] = 'r.timecreated < :endtime';
            $params['endtime'] = $this->_formdata->endtime;
        }

        $wherecondition = implode(' AND ', $conditions);
        
        $records = $DB->get_records_sql(
            'SELECT r.* FROM {data_records} r JOIN {data} d ON r.dataid = d.id WHERE ' . $wherecondition,
            $params);
        return $records;
    }

    /**
     * Prepare report results by creating an associative array with keys
     *
     * @return \report_dbmetrics\resultrecord[]
     */
    private function prepareresults()
    {
        global $DB;
        if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_INDIVIDUAL) {
            $users = enrol_get_course_users($this->_courseid);
            $results = [];
            foreach ($users as $user) {
                $usergroups = report_dbmetrics_usergetgroups($user->id, $this->_courseid);
                $groupids = array_map(function ($group) { return $group->id; }, $usergroups);
                if (!count(array_intersect($groupids, $this->_formdata->groups))) {
                    continue;
                }

                $groupnames = implode(' / ', array_map(function ($group) { return $group->name; }, $usergroups));

                $results[$user->id] = new resultrecord_individual($user, null, $groupnames);
            }
            return $results;
        } else if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_GROUP) {
            $groups = groups_get_all_groups($this->_courseid);
            $results = [];
            foreach ($groups as $group) {
                if (!in_array($group->id, $this->_formdata->groups)) {
                    continue;
                }
                $results[$group->id] = new resultrecord_group($group);
            }
            return $results;
        } else if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_MODULE) {
            $results = [];
            foreach ($this->_formdata->modules as $dataid) {
                $data = $DB->get_record('data', ['id' => $dataid, 'course' => $this->_courseid]);
                if (!$data) {
                    continue;
                }
                $results[$data->id] = new resultrecord_module($data);
            }
            return $results;
        }
        throw new moodle_exception('Unknown filter type');
    }

    /**
     * Get the keys of results array that will be affected by the database record
     *
     * @param object $datarecord
     * @return int[]
     */
    private function gettargetresultrecordkeys($datarecord) {
        if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_INDIVIDUAL) {
            return [$datarecord->userid];
        } else if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_GROUP) {
            return report_dbmetrics_usergetgroupids($datarecord->userid, $this->_courseid);
        } else if ($this->_formdata->type == REPORT_DBMETRICS_TYPE_MODULE) {
            return [$datarecord->dataid];
        }
        throw new moodle_exception('Unknown filter type');
    }

    /**
     * Get final report column headers in string key names
     * 
     * @return string[]
     */
    public function getheader()
    {
        switch ($this->_formdata->type)
        {
            case REPORT_DBMETRICS_TYPE_INDIVIDUAL:
                return resultrecord_individual::getheader();
            case REPORT_DBMETRICS_TYPE_GROUP:
                return resultrecord_group::getheader();
            case REPORT_DBMETRICS_TYPE_MODULE:
                return resultrecord_module::getheader();
        }
        throw new moodle_exception('Unknown filter type');
    }

    /**
     * Get stringified final report column headers
     *
     * @return string[]
     */
    public function getstringedheader()
    {
        return array_map(function($header) { return get_string("header_{$header}", 'report_dbmetrics'); }, $this->getheader());
    }

    /**
     * Get HTML table of final report results
     *
     * @return html_table
     */
    public function gettable()
    {
        if ($this->_formdata->format != REPORT_DBMETRICS_FORMAT_TABLE) {
            throw new \coding_exception('Method gettable() should be called only when output format is table');
        }
        $table = new html_table();
        $table->head = $this->getstringedheader();
        $table->data = [];

        $results = $this->getresults();
        foreach ($results as $result)
        {
            $table->data[] = $result->getcontent();
        }
        
        return $table;
    }

    /**
     * Download final report results as file
     *
     * @return void
     */
    public function download()
    {
        if ($this->_formdata->format == REPORT_DBMETRICS_FORMAT_TABLE) {
            throw new \coding_exception('Method download() should be called when output format is not table');
        }
        $fields = $this->getstringedheader();
        $results = $this->getresults();

        \core\dataformat::download_data(
            clean_filename('database_report'),
            $this->_formdata->format,
            $fields,
            $results,
            function ($result) {
                return $result->getcontent();
            }
        );
    }

    /**
     * Add the value into specified keys of results array
     *
     * @param \report_dbmetrics\resultrecord[] $results
     * @param int[] $keys
     * @param string $property
     * @param int $value
     * @return void
     */
    private static function sumtargetresultrecord(&$results, $keys, $property, $value) {
        foreach ($keys as $key) {
            if (!isset($results[$key])) {
                continue;
            }
            if (!property_exists($results[$key], $property)) {
                throw new coding_exception("Property {$property} not found");
            }
            if (is_null($results[$key]->$property)) {
                $results[$key]->$property = 0;    
            }
            $results[$key]->$property += $value;
        }
    }

    /**
     * Set the value to specified keys of results array
     *
     * @param \report_dbmetrics\resultrecord[] $results
     * @param int[] $keys
     * @param string $property
     * @param int $value
     * @return void
     */
    private static function settargetresultrecord(&$results, $keys, $property, $value) {
        foreach ($keys as $key) {
            if (!isset($results[$key])) {
                continue;
            }
            if (!property_exists($results[$key], $property)) {
                throw new coding_exception("Property {$property} not found");
            }
            $results[$key]->$property = $value;
        }
    }
}
