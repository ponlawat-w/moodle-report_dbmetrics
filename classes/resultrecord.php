<?php

namespace report_dbmetrics;

abstract class resultrecord
{
    public $entries = 0;
    public $words = 0;
    public $multimedia = 'N/A';
    public $uniquedays = 'N/A';

    abstract public static function getheader();
    abstract public function getcontent();
}

class resultrecord_individual extends resultrecord
{
    public $username = null;
    public $firstname = null;
    public $lastname = null;

    public $groupname = null;

    public $commentsmade = 0;
    public $commentsreceived = 0;

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

class resultrecord_group extends resultrecord
{
    public $groupname = null;

    public $commentsmade = 0;
    public $commentsreceived = 0;

    public function __construct($group = null)
    {
        if ($group) {
            $this->groupname = $group->name;
        }
    }

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

class resultrecord_module extends resultrecord
{
    public $name = null;
    public $totalcomments = 0;

    public function __construct($datarecord = null)
    {
        if ($datarecord) {
            $this->name = $datarecord->name;
        }
    }

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
