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
 * Render an attempt at a HotPot quiz
 * Output format: html
 *
 * @package   mod-hotpot
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// get parent class
require_once($CFG->dirroot.'/mod/hotpot/attempt/renderer.php');

class mod_hotpot_attempt_html_renderer extends mod_hotpot_attempt_renderer {

    // strings to mark beginning and end of submission form
    protected $BeginSubmissionForm = '<!-- BeginSubmissionForm -->';
    protected $EndSubmissionForm = '<!-- EndSubmissionForm -->';

    // the id/name of the of the form which returns results to the browser
    protected $formid = 'store';

    // the name of the score field in the results returned form the browser
    protected $scorefield = 'mark';

    /**
     * preprocessing
     */
    function preprocessing() {
        global $CFG;

        if ($this->cache_uptodate) {
            $this->fix_title();
            $this->fix_links(true);
            $this->fix_submissionform();
            return true;
        }

        $this->xmldeclaration = '';
        $this->doctype = '';
        $this->htmlattributes = '';
        $this->headattributes = '';
        $this->headcontent = '';
        $this->bodyattributes = '';
        $this->bodycontent = '';

        if (! $this->hotpot->source->get_filecontents()) {
            // empty source file - shouldn't happen !!
            return false;
        }
        $this->htmlcontent = &$this->hotpot->source->filecontents;

        // extract contents of first <head> tag
        if (preg_match($this->tagpattern('head'), $this->htmlcontent, $matches)) {
            $this->headcontent = $matches[2];
        }

        if ($this->usemoodletheme) {
            // remove the title from the <head>
            $this->headcontent = preg_replace($this->tagpattern('title'), '', $this->headcontent);
        } else {
            // replace <title> with current name of this quiz
            $title = '<title>'.$this->get_title().'</title>'."\n";
            $this->headcontent = preg_replace($this->tagpattern('title'), $title, $this->headcontent);

            // extract details needed to rebuild page later in $this->view()
            if (preg_match($this->tagpattern('\?xml','',false), $this->htmlcontent, $matches)) {
                $this->xmldeclaration = $matches[0]."\n";
            }
            if (preg_match($this->tagpattern('!DOCTYPE','',false,'(?:<!--\s*)?','(?:\s*-->)?'), $this->htmlcontent, $matches)) {
                $this->doctype = $this->single_line($matches[0])."\n";
            }
            if (preg_match($this->tagpattern('html','',false), $this->htmlcontent, $matches)) {
                $this->htmlattributes = ' '.$this->single_line($matches[1])."\n";
            }
            if (preg_match($this->tagpattern('head','',false), $this->htmlcontent, $matches)) {
                $this->headattributes = ' '.$this->single_line($matches[1]);
            }
        }

        // transfer <styles> tags from $this->headcontent to $this->styles
        $this->styles = '';
        if (preg_match_all($this->tagpattern('style'), $this->headcontent, $matches, PREG_OFFSET_CAPTURE)) {
            foreach (array_reverse($matches[0]) as $match) {
                // $match: [0] = matched string, [1] = offset to start of string
                $this->styles = $match[0]."\n".$this->styles;
                $this->headcontent = substr_replace($this->headcontent, '', $match[1], strlen($match[0]));
            }
            if ($this->usemoodletheme) {
                // restrict scope of page styles, so they affect only the quiz's containing element (i.e. the middle column)
                $search = '/([a-z0-9_\#\.\-\,\: ]+){(.*?)}/ise';
                $replace = '$this->fix_css_definitions("#'.$this->themecontainer.'","\\1","\\2")';
                $this->styles = preg_replace($search, $replace, $this->styles);

                // the following is not necessary for standard HP styles, but may required to handle some custom styles
                $this->styles = str_replace('TheBody', 'mod-hotpot-view', $this->styles);
            }
            $this->styles = $this->remove_blank_lines($this->styles);
        }

        // transfer <script> tags from $this->headcontent to $this->scripts
        $this->scripts = '';
        if (preg_match_all($this->tagpattern('script'), $this->headcontent, $matches, PREG_OFFSET_CAPTURE)) {
            foreach (array_reverse($matches[0]) as $match) {
                // $match: [0] = matched string, [1] = offset to start of string
                $this->scripts = $match[0]."\n".$this->scripts;
                $this->headcontent = substr_replace($this->headcontent, '', $match[1], strlen($match[0]));
            }
            // remove block and single-line comments - except <![CDATA[ + ]]>> and  <!-- + --> and http(s)://
            if ($CFG->debug <= DEBUG_DEVELOPER) {
                $this->scripts = preg_replace('/\s*\/\*.*?\*\//s', '', $this->scripts);
                $this->scripts = preg_replace('/\s*([a-z]+:)?\/\/[^\n\r]*/ise', '$this->fix_js_comment("\\0","\\1")', $this->scripts);
            }
            $this->scripts = $this->remove_blank_lines($this->scripts);

            // standardize "} else {" formatting
            $this->scripts = preg_replace('/}\s*else\s*{/s', '} else {', $this->scripts);
        }

        // remove blank lines
        $this->headcontent = $this->remove_blank_lines($this->headcontent);

        // put each <meta> tag on its own line
        $this->headcontent = preg_replace('/'.'([^\n])'.'(<\w+)'.'/', "\\1\n\\2", $this->headcontent);

        // append styles and scripts to the end of the $this->headcontent
        $this->headcontent .= $this->styles.$this->scripts;

        // extract <body> tag
        if (! preg_match($this->tagpattern('body'), $this->htmlcontent, $matches)) {
            return false;
        }

        $this->bodyattributes = $this->single_line(preg_replace('/\s*id="[^"]*"/', '', $matches[1]));
        $this->bodycontent = $this->remove_blank_lines($matches[2]);

        // fix self-closing <script /> tags, as they cause several browsers to ignore following content
        $this->bodycontent = preg_replace('/(<script[^>]*)\/>/is', '\\1></script>', $this->bodycontent);

        if (preg_match('/\s*onload="([^"]*)"/is', $this->bodyattributes, $matches, PREG_OFFSET_CAPTURE)) {
            $this->bodyattributes = substr_replace($this->bodyattributes, '', $matches[0][1], strlen($matches[0][0]));
            if ($this->usemoodletheme) {
                // workaround to ensure javascript onload routine for quiz is always executed
                // $this->bodyattributes will only be inserted into the <body ...> tag
                // if it is included in the theme/$CFG->theme/header.html,
                // so some old or modified themes may not insert $this->bodyattributes
                $this->bodycontent .= $this->fix_onload($matches[1][0], true);
            }
        }
        $this->fix_title();
        $this->fix_relativeurls();
        $this->fix_mediafilter();
        $this->fix_links();
    }

    /**
     * fix_js_comment
     *
     * @param xxx $comment
     * @param xxx $protocol
     * @param xxx $quote (optional, default="'")
     * @return xxx
     */
    function fix_js_comment($comment, $protocol, $quote="'")  {
        if ($quote) {
            // fix quotes escaped by preg_replace
            $comment = str_replace('\\'.$quote, $quote, $comment);
            $protocol = str_replace('\\'.$quote, $quote, $protocol);
        }
        if ($protocol || preg_match('/^\s*\/\/((?:<!\[CDATA\[)|(?:<!--)|(?:-->)|(?:\]\]>))/', $comment)) {
            return $comment;
        } else {
            return '';
        }
    }

    /**
     * fix_links
     *
     * @param xxx $quickfix (optional, default=false)
     * @return xxx
     */
    function fix_links($quickfix=false)  {
        global $DB;

        if ($quickfix) {
            $search = '/(?<=["\/]attempt\.php\?id=)[0-9]+/';
            $this->bodycontent = preg_replace($search, $this->hotpot->attempt->id, $this->bodycontent);
            return true;
        }

        if (! preg_match_all('/<a[^>]*href="([^"]*)"[^>]*>/is', $this->bodycontent, $matches, PREG_OFFSET_CAPTURE)) {
            return false; // no links
        }

        $urls = array();
        $strlen = strlen($this->hotpot->source->baseurl);
        foreach ($matches[1] as $i=>$match) {
            $url = $this->convert_url_relative($this->hotpot->source->baseurl, $this->hotpot->source->filepath, '', $match[0], '', '');
            if (strpos($url, $this->hotpot->source->baseurl.'/')===0) {
                $urls[$i] = addslashes(substr($url, $strlen+1));
            }
        }

        if (! count($urls)) {
            return false; // no links to files in this course
        }

        $select = "course=".$this->hotpot->course->id." AND sourcefile IN ('".implode("','", $urls)."')";
        if ($quizzes = $DB->get_records_select('hotpot', $select, null, 'id', 'id,sourcefile')) {
            foreach ($quizzes as $quiz) {
                $i = array_search($quiz->sourcefile, $urls);
                $matches[1][$i][2] = $quiz->id;
            }
            foreach (array_reverse($matches[1]) as $match) {
                // $match [0] old url, [1] offset [2] quizid
                if (array_key_exists(2, $match)) {
                    $newurl = 'view.php?id='.$this->hotpot->attempt->id.'&amp;status='.hotpot::STATUS_COMPLETED.'&amp;redirect='.$match[2];
                    $this->bodycontent = substr_replace($this->bodycontent, $newurl, $match[1], strlen($match[0]));
                }
            }
        }
    }

    /**
     * postprocessing
     */
    function postprocessing()  {
        $this->fix_title_icons();
        $this->fix_submissionform();
    }

    /**
     * fix_title
     */
    function fix_title()  {
        if (preg_match($this->tagpattern('h2'), $this->bodycontent, $matches, PREG_OFFSET_CAPTURE)) {
            // $matches: <h2 $matches[1]>$matches[2]</h2>
            $start = $matches[2][1];
            $length = strlen($matches[2][0]);
            $this->bodycontent = substr_replace($this->bodycontent, $this->get_title(), $start, $length);
        }
    }

    /**
     * fix_title_icons
     */
    function fix_title_icons()  {
        // add quiz edit icons if the current user is a teacher/administrator
        if (has_capability('mod/hotpot:manage', $this->hotpot->context)) {
            if (preg_match($this->tagpattern('h2'), $this->bodycontent, $matches, PREG_OFFSET_CAPTURE)) {
                // $matches: <h2 $matches[1]>$matches[2]</h2>
                $start = $matches[2][1] + strlen($matches[2][0]);
                $url = new moodle_url('/course/modedit.php', array('update' => $this->hotpot->cm->id, 'return' => 1, 'sesskey' => sesskey()));
                $img = html_writer::empty_tag('img', array('src' => $this->pix_url('t/edit')));
                $this->bodycontent = substr_replace($this->bodycontent, html_writer::link($url, $img), $start, 0);
            }
        }
    }

    /**
     * fix_submissionform
     */
    function fix_submissionform()  {

        // remove previous submission form, if any
        $search = '/\s*('.$this->BeginSubmissionForm.')\s*(.*?)\s*('.$this->EndSubmissionForm.')/s';
        $this->bodycontent = preg_replace($search, '', $this->bodycontent);

        // prepare form parameters and attributes
        $params = array(
            'id' => $this->hotpot->create_attempt(),
            $this->scorefield => '0', 'detail' => '0', 'status' => '0',
            'starttime' => '0', 'endtime' => '0', 'redirect' => '0',
        );

        // add scorefield to params, if necessary (usually it is necessary)
        if (! preg_match('/<(input|select)[^>]*name="'.$this->scorefield.'"[^>]*>/is', $this->bodycontent)) {
            $params[$this->scorefield] = isset($this->hotpot->gradelimit) ? $this->hotpot->gradelimit : 100;
        }

        $attributes = array(
            'id' => $this->formid, 'autocomplete' => 'off'
        );

        // prepare continue button
        $continuebutton = html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('continue')));
        if ($this->usemoodletheme) {
            $continuebutton = html_writer::tag('div', $continuebutton, array('class'=>'continuebutton'));
        } else {
            $continuebutton = html_writer::tag('div', $continuebutton, array('align'=>'center'));
        }

        // wrap submission form around main content
        $this->bodycontent = ''
            .$this->BeginSubmissionForm."\n"
            .$this->form_start('submit.php', $params, $attributes)
            .$this->EndSubmissionForm."\n"
            .$this->bodycontent."\n"
            .$this->BeginSubmissionForm."\n"
            .$continuebutton."\n"
            .$this->form_end()."\n"
            .$this->EndSubmissionForm."\n"
        ;
    }
}
