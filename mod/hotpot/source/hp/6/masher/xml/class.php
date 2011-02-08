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
 * Source type: hp_6_masher_xml
 *
 * @package   mod-hotpot
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// get parent class
require_once($CFG->dirroot.'/mod/hotpot/source/hp/6/masher/class.php');

class hotpot_source_hp_6_masher_xml extends hotpot_source_hp_6_masher {

    /**
     * is_unitfile
     *
     * @return xxx
     */
    function is_unitfile()  {
        if (empty($this->fullpath)) {
            // there's no path
            return false;
        }

        if (! preg_match('/\.(jms)$/', $sourcefile->get_filename())) {
            // this is not a Hot Potatoes masher file
            return false;
        }

        if (! $content = $sourcefile->get_content()) {
            // empty or non-existant file
            return false;
        }

        // create xml tree for this file
        $xml = xmlize($content, 0);

        // check we have the expected xml tree structure
        $root_tag = self::hbs_software.'-'.self::hbs_quiztype.'-file';
        if (empty($xml[$root_tag]['#']['hotpot-file-list'][0]['#'])) {
            // could not detect config file settings for this Hot Potatoes quiz - shouldn't happen !!
            return false;
        }

        // shortcut to the file list in this Hot Potatoes masher file
        $filelist = &$xml[$root_tag]['#']['hotpot-file-list'][0]['#'];
        $quizzes = array();

        $i = 0;
        while (isset($filelist['hotpot-file'][$i]['#'])) {

            // shortcut to the file info for this file
            $file = &$filelist['hotpot-file'][$i]['#'];
            // $file['data-file-name'][0]['#'] : C:\My Documents\HotPots\jquiz.jqz
            // $file['output-file-name'][0]['#']: jquiz.htm
            // $file['next-ex-file-name'][0]['#'] : jquiz-v6.htm
            // $file['output-type'][0]['#'] : 2

            $filename = '';
            if (isset($file['output-file-name'][0]['#'])) {
                if (is_readable($this->dirname.'/'.$file['output-file-name'][0]['#'])) {
                    $filename = $file['output-file-name'][0]['#'];
                }
            }
            if (! $filename) {
                // output file was not found, so look for the original data file
                if (isset($file['data-file-name'][0]['#'])) {
                    if (is_readable($this->dirname.'/'.$file['data-file-name'][0]['#'])) {
                        $filename = $file['data-file-name'][0]['#'];
                    }
                }
            }
            if ($filename) {
                // add filepath
                $quizzes[] = dirname($this->filepath).'/'.$filename;
            } else {
                print $i.'invalid file name: output:'.$file['output-file-name'][0]['#'].', input:'.$file['data-file-name'][0]['#'].'<br />';
            }
            $i++;
        } // end while

        if (count($quizzes)) {
            return $quizzes;
        } else {
            return false;
        }
    }

    /**
     * get_name
     *
     * @param xxx $textonly (optional, default=true)
     * @return xxx
     */
    function get_name($textonly=true)  {
        if (! isset($this->name)) {
            $this->name = '';
            $this->title = '';

            if (! $this->xml_get_filecontents()) {
                // could not detect Hot Potatoes quiz type - shouldn't happen !!
                return false;
            }

            $this->title = $this->xml_value('unit-title');
            $this->name = trim(striptags($this->title));
        }
        if ($textonly) {
            return $this->name;
        } else {
            return $this->title;
        }
    }

    /**
     * get_title
     *
     * @return xxx
     */
    function get_title()  {
        return $this->get_name(false);
    }
}
