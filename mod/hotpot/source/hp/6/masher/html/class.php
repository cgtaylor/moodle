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
 * Source type: hp_6_masher_html
 *
 * @package   mod-hotpot
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// get parent class
require_once($CFG->dirroot.'/mod/hotpot/source/hp/6/masher/class.php');

class hotpot_source_hp_6_masher_html extends hotpot_source_hp_6_masher {

    /**
     * is_unitfile
     *
     * @return xxx
     */
    function is_unitfile()  {
        if (! preg_match('/\.html?$/', $sourcefile->get_filename())) {
            // wrong file type
            return false;
        }

        if (! $content = $sourcefile->get_content()) {
            // empty or non-existant file
            return false;
        }

        if (! preg_match('/<!\-\- Made with executable version HotPotatoes: Masher Version [^>]* \-\->/is', $content)) {
            // not a masher index.htm
            return false;
        }

        if (! preg_match('/<ul class="Index"[^>]*>(.*?)<\/ul>/is', $content, $list)) {
            // no list of links - shouldn't happen
            return false;
        }

        // isolate items from the list of links
        if (! preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $list[1], $items)) {
            // empty list - shouldn't happen
            return false;
        }

        $quizzes = array();

        // isolate the URL and title for each of the items from the list of links
        foreach ($items[1] as $item) {
            if (preg_match('/<a href="(.*?)">(.*?)<\/a>/is', $item, $matches)) {
                if (is_readable($this->dirname.'/'.$matches[1])) {
                    // N.B. $matches[2] holds the quiz name
                    $quizzes[] = dirname($this->filepath).'/'.$matches[1];
                }
            }
        }

        if (count($quizzes)) {
            return $quizzes;
        } else {
            return false;
        }
    }
}
