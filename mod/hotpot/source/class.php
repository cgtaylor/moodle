<?php

// This file is part of Moodle - http://moodle.org/
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
 * Class to represent the source of a HotPot quiz
 *
 * @package   mod-hotpot
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class hotpot_source {
    /** @var stored_file object representing stored file */
    public $file;

    /** @var int the course id associated with this object */
    public $courseid = 0;

    /** @var int the file's location (0 : site files, 1 : course files) */
    public $location = 0;

    /** @var int the course folder within the Moodle data folder (either $courseid or SITEID) */
    public $coursefolder = 0;

    /** @var string the full path to the course folder (i.e. $CFG->dataroot.'/'.$coursefolder) */
    public $basepath = '';

    /** @var string the file's path (relative to $basepath) */
    public $filepath = '';

    /** @var string the full path to this file (i.e. $basepath.'/'.$filepath) */
    public $fullpath = '';

    /** @var string the full path to the folder containing this file */
    public $dirname  = '';

    /** @var string the file name */
    public $filename = '';

    /** @var string the URL of this file */
    public $url = '';

    /** @var string the base url for this file */
    public $baseurl = '';

    /** @var string the contents of the source file */
    public $filecontents;

    /*
     * properties for efficiently fetching remotely hosted files using Conditional GET
     */

    /** @var string remote server's representation of time file was last modified */
    public $lastmodified = '';

    /** @var string (md5?) key indentifying remote file */
    public $etag = '';

    /** @var string remote server's representation of current time */
    public $date = '';

    /*
     * properties for a unit source file (e.g. a Hot Potatoes Masher file)
     */

    /** @var string the unit name extracted from the source file */
    public $unitname;

    /** @var string the unit entry text, extracted from the source file */
    public $unitentrytext;

    /** @var string the unit exit text, extracted from the source file */
    public $unitexittext;

    /** @var string array of hotpot_source objects for quizzes in this unit */
    public $quizfiles;

    /*
     * properties of the icon for this source file type
     */

    /** @var string the path (below $CFG->wwwroot) to the icon for this file */
    public $icon = 'mod/hotpot/icon.gif';

    /** @var string the display width for this file's icon */
    public $iconwidth = '16';

    /** @var string the display height for this file's icon */
    public $iconheight = '16';

    /** @var string the css class this file's icon */
    public $iconclass = 'icon';

    /**
     * output formats for this file type
     */

    /** @var string output formats which can use this file type */
    public $outputformats;

    /** @var string the best output format type for this file */
    public $best_outputformat;

    /**
     * properties of the quiz file - each one has a correspinding get_xxx() function
     */

    /** @var string the name of the quiz that is displayed on the list of quizzes in this unit */
    public $name;

    /** @var string the title the is displayed when this quiz is viewed in a browser */
    public $title;

    /** @var string the text, if any, that could be used on the unit's entry page */
    public $entrytext;

    /** @var string the text, if any, that could be used on the unit's entry page */
    public $exittext;

    /** @var string the next quiz, if any, in this chain */
    public $nextquiz;

    /**
     * Creates a hotpot_source object and can optionally prepare the file contents
     * ready to be passed to a HotPot output format classs.
     *
     * @param stdclass $file Moodle stored_file object representing the file
     */
    public function __construct($file_or_string, $courseid=0, $location=0) {
        global $CFG;

        if (is_object($file_or_string)) {
            // view hotpot activity
            $this->file = $file_or_string;
            $this->filepath = ltrim($this->file->get_filepath().$this->file->get_filename(), '/');
        } else if (is_string($file_or_string)) {
            // question import
            $this->file = false;
            $this->filepath = '';
            $this->filecontents = $file_or_string;
        }

        $this->location = $location;
        $this->courseid = $courseid;

        if ($location==1) {
            // 1=LOCATION_SITEFILES
            // only used on HotPots upgraded from Moodle 1.9
            // and even then, it would be unusual on most sites
            $this->coursefolder = SITEID;
        } else {
            $this->coursefolder = $this->courseid;
        }
        $this->basepath = $CFG->dataroot.'/'.$this->coursefolder;
        $this->fullpath = $this->basepath.'/'.$this->filepath;

        $this->dirname  = dirname($this->fullpath);
        $this->filename = basename($this->fullpath);

        if ($CFG->slasharguments) {
            $this->baseurl = $CFG->wwwroot.'/file.php/'.$this->coursefolder;
        } else {
            $this->baseurl = $CFG->wwwroot.'/file.php?file=/'.$this->coursefolder;
        }
    }

    /**
     * hotpot_source_old_constructor
     *
     * @param xxx $file
     * @param xxx $location
     * @param xxx $getquizfiles
     * @param xxx $getquizchain
     * @return xxx
     */
    public function hotpot_source_old_constructor($file, $location, $getquizfiles=false, $getquizchain=false) {
        // this is the function that was used in Moodle <= 1.9
        global $CFG, $course;

        if (empty($file)) {
            return false;
        }
        if (preg_match('|^https?://|', $file)) {
            $this->url = $file;
            $this->location = hotpot::LOCATION_WWW;

            if ($parse_url = parse_url($file)) {
                $this->dirname  = dirname($parse_url['path']);
                $this->filename = basename($parse_url['path']);
            }

        } else {
            $this->filepath = rtrim($file, '/\\');
            $this->location = $location;
            if (is_object($course)) {
                $this->courseid = $course->id;
            } else {
                $this->courseid = $course;
            }
            switch ($location) {
                case hotpot::LOCATION_SITEFILES:
                    $this->coursefolder = SITEID;
                    break;
                case hotpot::LOCATION_COURSEFILES:
                    $this->coursefolder = $this->courseid;
                    break;
            }

            $this->basepath = $CFG->dataroot.'/'.$this->coursefolder;
            $this->fullpath = $this->basepath.'/'.$this->filepath;

            $this->dirname  = dirname($this->fullpath);
            $this->filename = basename($this->fullpath);

            if ($CFG->slasharguments) {
                $this->baseurl = $CFG->wwwroot.'/file.php/'.$this->coursefolder;
            } else {
                $this->baseurl = $CFG->wwwroot.'/file.php?file=/'.$this->coursefolder;
            }
        }
    } // end function

    /*
     * This function will collect a list of quiz files associated with this file
     *
     * If the source file is ...
     *    a unit folder, a list of quiz files within the folder is returned
     *    a unit file, a list of quiz files listed in the file is returned
     *    the head of a quiz chain, a list of all quizzes in the chain is returned
     *
     * @param stdclass $file a Moodle stored_file object
     * @param int $getquizchain the "Add/Edit quiz chain?" value from mod_form.php
     * @return array $quizfiles ($path => $type) of HotPot quiz files
     */
    public static function get_quizfiles($sourcefile, $getquizchain) {
        if (! $quizfiles = self::get_quizfiles_in_unitfolder($sourcefile)) {
            if (! $quizfiles = self::get_quizfiles_in_unitfile($sourcefile)) {
                if (! $quizfiles = self::get_quizfiles_in_quizchain($sourcefile, $getquizchain)) {
                    // no recognized unit or quiz files found
                    $quizfiles = array();
                }
            }
        }
        return $quizfiles;
    }

    /*
     * This function will return either
     * a list of HotPot quiz files within $this->fullpath,
     * or false if there are no such files
     *
     * @return mixed
     *         array $quizfiles ($path => $type) HotPot quiz files in this folder
     *         boolean : false : no quiz files found
     */
    function get_quizfiles_in_unitfolder() {
        $quizfiles = array();

        if (! $this->fullpath) {
            // not a local path
            return false;
        }

        if (! is_dir($this->fullpath)) {
            // not a folder
            return false;
        }

        if ($dh = @opendir($this->fullpath)) {
            $paths = array();
            while ($file = @readdir($dh)) {
                if (is_file("$this->fullpath/$file")) {
                    $paths[] = "$this->filepath/$file";
                }
            }
            closedir($dh);

            sort($paths);
            foreach ($paths as $path) {
                if ($quiz = $this->is('is_quizfile', $path, $this->location)) {
                    $quizfiles[] = $quiz;
                }
            }
        }

        if (count($quizfiles)) {
            return $quizfiles;
        } else {
            return false;
        }
    }

    // returns an array of hotpot_source objects if $filename is a head of a quiz chain, or false otherwise
    function get_quizfiles_in_quizchain($getquizchain) {
        $quizfiles = array();

        if ($this->location==hotpot::LOCATION_WWW) {
            $path = $this->url;
        } else {
            $path = $this->filepath;
        }

        while ($path && ($quiz = $this->is('is_quizfile', $path, $this->location))) {

            // add this quiz
            $quizfiles[] = $quiz;

            if ($getquizchain) {
                // get next quiz (if any)
                if ($path = $quiz->get_nextquiz()) {
                    // to prevent infinite loops on chains, we check that
                    // the next quiz is not one of the earlier quizzes
                    foreach ($quizfiles as $quizfile) {
                        if ($quizfile->filepath==$path) {
                            $path = false;
                        }
                    }
                }
            } else {
                // force end of loop
                $path = false;
            }
        }

        if (count($quizfiles)) {
            return $quizfiles;
        } else {
            return false;
        }
    }

    // return array of
    function get_quizfiles_in_unitfile() {
        $quizfiles = array();

        if ($this->location==hotpot::LOCATION_WWW) {
            $path = $this->url;
        } else {
            $path = $this->filepath;
        }

        if (! $paths = $this->is('is_unitfile', $path, $this->location)) {
            return false;
        }

        foreach ($paths as $path) {
            if ($quiz = $this->is('is_quizfile', $path, $this->location)) {
                $quizfiles[] = $quiz;
            }
        }

        if (count($quizfiles)) {
            return $quizfiles;
        } else {
            return false;
        }
    }

    /*
     * Given a class method name, a full path to a file and relative path to plugins directory,
     * this function will get quiz type classes from the plugins directory (and subdirectories),
     * and search the classes for a method which returns a non-empty result
     *
     * @param string $methodname : name of a method to be used in the classes in the plugins directory
     * @param string $fullpath :  to a file, which may be a HotPot quiz or unit file
     * @return mixed : whatever the result that is return from the $methodname called on the classes
     */
    function is($methodname, $path, $location) {
        $result = false;

        $types = hotpot_get_classes('file');
        foreach ($types as $type) {

            //if ($result = $type::$methodname($fullpath)) {
            $object = new $type($path, $location);
            if (method_exists($object, $methodname)) {

                // give the quiz object access to this object
                if ($result = $object->$methodname()) {
                    // if this is the first unit/quiz file to be recognized, then store the name
                    // because if $form->namesource==HOTPOT_NAMESOURCE_QUIZ,
                    // $this->unitname may be used later as the name of the HotPot activity
                    if (empty($this->unitname)) {
                        $this->unitname = $object->get_name();
                    }
                    if (empty($this->unitentrytext)) {
                        $this->unitentrytext = $object->get_entrytext();
                    }
                    if (empty($this->unitexittext)) {
                        $this->unitexittext = $object->get_exittext();
                    }
                    break;
                }
            }
        }
        return $result;
    }

    /*
     * Returns source/output type of this file
     *
     * @param string a hotpot file/output class name
     * @return string class name without the leading "hotpot_source_"
     */
    public static function get_type($class='') {
        return preg_replace('/^hotpot_[a-z]+_/', '', $class);
    }

    /*
     * Returns true if $sourcefile is quiz file, or false otherwise
     *
     * @param stdclass $sourcefile a Moodle stored_file object representing the source file
     * @return boolean true if the file is a recognized quiz file, or false otherwise
     */
    public static function is_quizfile($sourcefile) {
        return false;
    }

    /*
     * Returns array of filepaths if $sourcefile is a unit file, or false otherwise
     *
     * @param stdclass $sourcefile a Moodle stored_file object representing the source file
     * @return boolean true if the file is a recognized quiz file, or false otherwise
     */
    function is_unitfile($sourcefile) {
        return false;
    }

    // returns name of quiz that is displayed in the list of quizzes
    function get_name() {
        return '';
    }

    // returns title of quiz when it is viewed in a browser
    function get_title() {
        return '';
    }

    // returns the entry text for a quiz
    function get_entrytext() {
        return '';
    }

    // returns the exit text for a quiz
    function get_exittext() {
        return '';
    }

    // returns $filepath of next quiz if there is one, or false otherwise
    function get_nextquiz() {
        return false;
    }

    // returns an <img> tag for the icon for this source file type
    function get_icon() {
        global $CFG;
        if (preg_match('/^(?:https?:)?\/+/', $this->icon)) {
            $icon = $this->icon;
        } else {
            $icon = $CFG->wwwroot.'/'.$this->icon;
        }
        return '<img src="'.$icon.'" width="'.$this->iconwidth.'" height="'.$this->iconheight.'" class="'.$this->iconclass.'" />';
    }

    // property access functions

    // returns file (=either url or filepath)
    function get_file() {
        if ($this->location==hotpot::LOCATION_WWW) {
            return $this->url;
        }
        if ($this->filepath) {
            return $this->filepath;
        }
        return false;
    }

    // returns location (0 : coursefiles; 1 : site files; false : undefined) of quiz source file
    function get_location($courseid) {
        if ($this->coursefolder) {
            if ($this->coursefolder==$courseid) {
                return hotpot::LOCATION_COURSEFILES;
            }
            if ($this->coursefolder==SITEID) {
                return hotpot::LOCATION_SITEFILES;
            }
        }
        if ($this->url) {
            return hotpot::LOCATION_WWW;
        }
        return false;
    }

    /**
     * filemtime
     *
     * @param xxx $lastmodified
     * @param xxx $etag
     * @return xxx
     */
    function filemtime($lastmodified, $etag)  {
        if (is_object($this->file)) {
            return $this->file->get_timemodified();
        }
        if ($this->url) {
            $headers = array(
                'If-Modified-Since'=>$lastmodified, 'If-None-Match'=>$etag
                // 'If-Modified-Since'=>'Wed, 23 Apr 2008 17:53:50 GMT',
                // 'If-None-Match'=>'"52237ffc6aa5c81:16d9"'
            );
            if ($this->get_filecontents_url($headers)) {
                if ($this->lastmodified) {
                    $filemtime = strtotime($this->lastmodified);
                } else {
                    $filemtime = strtotime($lastmodified);
                }
                if ($this->date) {
                    $filemtime += (time() - strtotime($this->date));
                }
                return $filemtime;
            } else {
                debugging('remote file not accesisble: '.$this->url, DEBUG_DEVELOPER);
                return 0;
            }
        }
        // not a local file or a remote file ?!
        return 0;
    }

    /**
     * get_filecontents
     *
     * @return xxx
     */
    function get_filecontents()  {
        if (isset($this->filecontents)) {
            return $this->filecontents ? true : false;
        }

        // initialize $this->filecontent
        $this->filecontents = false;

        if ($this->location==hotpot::LOCATION_WWW) {
            if (! $this->url) {
                // no url given - shouldn't happen
                return false;
            }
            if (! $this->get_filecontents_url()) {
                // url is (no longer) accessible
                return false;
            }
        } else {
            if (! $this->file) {
                // no file object - shouldn't happen !!
                return false;
            }

            // get the file contents
            if (! $this->filecontents = $this->file->get_content()) {
                // nothing in the file (or some problem with "file_set_bodycontents")
                return false;
            }
        }

        // file contents were successfully read

        // remove BOMs - http://en.wikipedia.org/wiki/Byte_order_mark
        switch (true) {
            case substr($this->filecontents, 0, 4)=="\xFF\xFE\x00\x00":
                $start = 4;
                $encoding = 'UTF-32LE';
                break;
            case substr($this->filecontents, 0, 4)=="\x00\x00\xFE\xFF":
                $start = 4;
                $encoding = 'UTF-32BE';
                break;
            case substr($this->filecontents, 0, 2)=="\xFF\xFE":
                $start = 2;
                $encoding = 'UTF-16LE';
                break;
            case substr($this->filecontents, 0, 2)=="\xFE\xFF":
                $start = 2;
                $encoding = 'UTF-16BE';
                break;
            case substr($this->filecontents, 0, 3)=="\xEF\xBB\xBF":
                $start = 3;
                $encoding = 'UTF-8';
                break;
            default:
                $start = 0;
                $encoding = '';
        }

        // remove BOM, if necessary
        if ($start) {
            $this->filecontents = substr($this->filecontents, $start);
        }

        // convert to UTF-8, if necessary
        if ($encoding=='' || $encoding=='UTF-8') {
            // do nothing
        } else if (function_exists('iconv')) {
            $this->filecontents = iconv($encoding, 'UTF-8', $this->filecontents);
        } else if (function_exists('mb_convert_encoding')) {
            $this->filecontents = mb_convert_encoding($this->filecontents, 'UTF-8', $encoding);
        }

        return true;
    }

    /**
     * get_filecontents_url
     *
     * @param xxx $headers (optional, default=null)
     * @return xxx
     */
    function get_filecontents_url($headers=null)  {
        global $CFG;
        require_once($CFG->dirroot.'/lib/filelib.php');

        $fullresponse = download_file_content($this->url, $headers, null, true);
        foreach ($fullresponse->headers as $header) {
            if ($pos = strpos($header, ':')) {
                $name = trim(substr($header, 0, $pos));
                $value = trim(substr($header, $pos+1));
                switch ($name) {
                    case 'Last-Modified': $this->lastmodified = trim($value); break;
                    case 'ETag': $this->etag = trim($value); break;
                    case 'Date': $this->date = trim($value); break;
                }
            }
        }
        if ($fullresponse->status==200) {
            $this->filecontents = $fullresponse->results;
            return true;
        }
        if ($fullresponse->status==304) {
            return true;
        }
        return false;
    }

    /**
     * compact_filecontents
     */
    function compact_filecontents()  {
        if (isset($this->filecontents)) {
            $this->filecontents = preg_replace('/(?<=>)'.'\s+'.'(?=<)/s', '', $this->filecontents);
        }
    }

    // return best output format for this file type
    // (eventually this should take account of current device and browser)
    function get_best_outputformat() {
        if (! isset($this->best_outputformat)) {
            // the default outputformat is the same as the sourcefile format
            // assuming class name starts with "hotpot_source_"
            $this->best_outputformat = substr(get_class($this), 14);
        }
        return $this->best_outputformat;
    }


    // synchonize file and Moodle settings
    function synchronize_moodle_settings(&$quiz) {
        return false;
    }
} // end class