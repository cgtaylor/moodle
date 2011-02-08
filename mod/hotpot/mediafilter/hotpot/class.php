<?php
// get the standard Moodle mediaplugin filter
require_once($CFG->dirroot.'/filter/mediaplugin/filter.php');

// get the parent class (=hotpot_mediafilter)
require_once($CFG->dirroot.'/mod/hotpot/mediafilter/class.php');

class hotpot_mediafilter_hotpot extends hotpot_mediafilter {

    /**
     * mediaplugin_filter
     *
     * @param xxx $courseid
     * @param xxx $text
     * @param xxx $options (optional, default=array)
     * @return xxx
     */
    function mediaplugin_filter($courseid, $text, $options=array())  {
        global $CFG, $hotpot;

        // Keep track of the id of the current quiz
        // so that eolas_fix.js is only included once in each quiz
        // Note: the cron script calls this method for multiple quizzes
        static $eolas_fix_applied = 0;

        if (! is_string($text)) {
            // non string data can not be filtered anyway
            return $text;
        }
        $newtext = $text; // fullclone is slow and not needed here

        foreach ($this->media_filetypes as $filetype) {

            // set $adminsetting, the name of the $CFG setting, if any, which enables/disables filtering of this file type
            $adminsetting = '';
            if (preg_match('/^[a-z]+$/', $filetype)) {
                $hotpot_enable = 'hotpot_enable'.$filetype;
                $filter_mediaplugin_enable = 'filter_mediaplugin_enable_'.$filetype;

                if (isset($CFG->$hotpot_enable)) {
                    $adminsetting = $hotpot_enable;
                } else if (isset($CFG->$filter_mediaplugin_enable)) {
                    $adminsetting = $filter_mediaplugin_enable;
                }
            }

            // set $search and $replace strings
            $search = '/<a.*?href="([^"?>]*\.'.$filetype.'[^">]*)"[^>]*>.*?<\/a>/ise';
            if ($adminsetting=='' || $CFG->$adminsetting) {
                // filtering of this file type is allowed
                $replace = '$this->hotpot_mediaplugin_filter($filetype, "\\0", "\\1", $options)';
            } else {
                // filtering of this file type is disabled
                $replace = '"\\1<br />".get_string("error_disabledfilter", "hotpot", "'.$adminsetting.'")';
            }

            // replace $search text with $replace text
            $newtext = preg_replace($search, $replace, $newtext, -1, $count);

            if ($count>0) {
                break;
            }
        }

        if (is_null($newtext) || $newtext==$text) {
            // error or not filtered
            return $text;
        }

        if ($eolas_fix_applied==$hotpot->id) {
            // do nothing - the external javascripts have already been included for this quiz
        } else {
            $newtext .= "\n".'<script type="text/javascript" src="'.$CFG->wwwroot.'/lib/ufo.js"></script>';
            $newtext .= "\n".'<script type="text/javascript" src="'.$CFG->wwwroot.'/filter/mediaplugin/eolas_fix.js" defer="defer"></script>';
            $eolas_fix_applied = $hotpot->id;
        }

        return $newtext;
    }

    /**
     * hotpot_mediaplugin_filter
     *
     * @param xxx $filetype
     * @param xxx $link
     * @param xxx $mediaurl
     * @param xxx $options
     * @param xxx $quote (optional, default="'")
     * @return xxx
     */
    function hotpot_mediaplugin_filter($filetype, $link, $mediaurl, $options, $quote="'")  {
        if ($quote) {
            // fix quotes that were escaped by preg_replace
            $link = str_replace('\\'.$quote, $quote, $link);
            $mediaurl = str_replace('\\'.$quote, $quote, $mediaurl);
        }

        // get a valid $player name
        if (isset($options['player'])) {
            $player = $options['player'];
        } else {
            $player = '';
        }
        if ($player=='') {
            $player = $this->defaultplayer;
        } else if (! array_key_exists($player, $this->players)) {
            debugging('Invalid media player requested: '.$player);
            $player = $this->defaultplayer;
        }

        // merge player options
        if ($player==$this->defaultplayer) {
            $options = array_merge($this->players[$player]->options, $options);
        } else {
            $options = array_merge($this->players[$this->defaultplayer]->options, $this->players[$player]->options, $options);
        }

        // generate content for required player
        $content = $this->players[$player]->generate($filetype, $link, $mediaurl, $options);

        return $content;
    }
}
