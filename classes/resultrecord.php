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

/**
 * Abstract class of a result entry
 * 
 * @package report_dbmetrics
 * @copyright 2022 Ponlawat Weerapanpisit
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class resultrecord
{
    /**
     * @var int Number of entries (database record counts)
     */
    public $entries = 0;

    /**
     * @var integer Number of words count
     */
    public $words = 0;

    /**
     * @var string Number of multimedia count
     */
    public $multimedia = 'N/A';

    /**
     * @var string Number of unique days count
     */
    public $uniquedays = 'N/A';

    /**
     * Result headers
     *
     * @return string[]
     */
    abstract public static function getheader();

    /**
     * Result contents
     *
     * @return array
     */
    abstract public function getcontent();
}

/**
 * Report result as individual type
 * 
 * @package report_dbmetrics
 * @copyright 2022 Ponlawat Weerapanpisit
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resultrecord_individual extends resultrecord
{
    /**
     * @var string|null
     */
    public $username = null;

    /**
     * @var string|null
     */
    public $firstname = null;

    /**
     * @var string|null
     */
    public $lastname = null;

    /**
     * @var string|null
     */
    public $groupname = null;

    /**
     * @var int
     */
    public $commentsmade = 0;

    /**
     * @var int
     */
    public $commentsreceived = 0;

    /**
     * Constructor
     *
     * @param object $user
     * @param int $courseid
     * @param string $groupname
     */
    public function __construct($user = null, $courseid = null, $groupname = null)
    {
        if ($user) {
            $this->username = $user->username;
            $this->firstname = $user->firstname;
            $this->lastname = $user->lastname;
            if ($courseid) {
                $groups = groups_get_user_groups($courseid, $user->id);
                $this->groupname = array_map(function ($group) { return $group->name; }, $groups);
            } else if ($groupname) {
                $this->groupname = $groupname;
            }
        }
    }

    /**
     * @return string[]
     */
    public static function getheader()
    {
        return [
            'username',
            'firstname',
            'lastname',
            'groupname',
            'entries',
            'words',
            'multimedia',
            'commentsmade',
            'commentsreceived',
            'uniquedays'
        ];
    }

    /**
     * @return array
     */
    public function getcontent()
    {
        return [
            $this->username,
            $this->firstname,
            $this->lastname,
            $this->groupname,
            $this->entries,
            $this->words,
            $this->multimedia,
            $this->commentsmade,
            $this->commentsreceived,
            $this->uniquedays
        ];
    }
}

/**
 * Report result as grupal type
 * 
 * @package report_dbmetrics
 * @copyright 2022 Ponlawat Weerapanpisit
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resultrecord_group extends resultrecord
{
    /**
     * @var string
     */
    public $groupname = null;

    /**
     * @var int
     */
    public $commentsmade = 0;

    /**
     * @var int
     */
    public $commentsreceived = 0;

    /**
     * Constructor
     *
     * @param object|null $group
     */
    public function __construct($group = null)
    {
        if ($group) {
            $this->groupname = $group->name;
        }
    }

    /**
     * @return string[]
     */
    public static function getheader()
    {
        return [
            'groupname',
            'entries',
            'words',
            'multimedia',
            'commentsmade',
            'commentsreceived',
            'uniquedays'
        ];
    }

    /**
     * @return array
     */
    public function getcontent()
    {
        return [
            $this->groupname,
            $this->entries,
            $this->words,
            $this->multimedia,
            $this->commentsmade,
            $this->commentsreceived,
            $this->uniquedays
        ];
    }
}

/**
 * Report result as modular type
 * 
 * @package report_dbmetrics
 * @copyright 2022 Ponlawat Weerapanpisit
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resultrecord_module extends resultrecord
{
    /**
     * @var string
     */
    public $name = null;

    /**
     * @var int
     */
    public $totalcomments = 0;

    /**
     * Constructor
     *
     * @param object $datarecord
     */
    public function __construct($datarecord = null)
    {
        if ($datarecord) {
            $this->name = $datarecord->name;
        }
    }

    /**
     * @return string[]
     */
    public static function getheader()
    {
        return [
            'name',
            'entries',
            'words',
            'multimedia',
            'totalcomments',
            'uniquedays'
        ];
    }
    
    /**
     * @return array
     */
    public function getcontent()
    {
        return [
            $this->name,
            $this->entries,
            $this->words,
            $this->multimedia,
            $this->totalcomments,
            $this->uniquedays
        ];
        
    }
}
