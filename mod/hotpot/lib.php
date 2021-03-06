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
 * Library of hotpot module functions needed by Moodle core and other subsystems
 *
 * All the functions neeeded by Moodle core, gradebook, file subsystem etc
 * are placed here.
 *
 * @package    mod
 * @subpackage hotpot
 * @copyright  2009 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information if the module supports a feature
 *
 * maybe this function should be called "mod_hotpot_supports"
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function hotpot_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:   return true;
        case FEATURE_GROUPINGS:         return true;
        case FEATURE_GROUPMEMBERSONLY:  return true;
        case FEATURE_BACKUP_MOODLE2:    return true;
        // disable features whose default is "true"
        case FEATURE_MOD_INTRO:         return false;
        default:                        return null;
    }
}

/**
 * Saves a new instance of the hotpot into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will save a new instance and return the id number
 * of the new instance.
 *
 * @param stdclass $data An object from the form in mod_form.php
 * @return int The id of the newly inserted hotpot record
 */
function hotpot_add_instance(stdclass $data, $mform) {
    global $DB;

    hotpot_process_formdata($data, $mform);

    // insert the new record so we get the id
    $data->id = $DB->insert_record('hotpot', $data);

    // update gradebook item
    hotpot_grade_item_update($data);

    return $data->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdclass $data An object from the form in mod_form.php
 * @return bool success
 */
function hotpot_update_instance(stdclass $data, $mform) {
    global $DB;

    hotpot_process_formdata($data, $mform);

    $data->id = $data->instance;
    $DB->update_record('hotpot', $data);

    // update gradebook item
    if ($data->grademethod==$mform->get_original_value('grademethod', 0)) {
        hotpot_grade_item_update($data);
    } else {
        // recalculate grades for all users
        hotpot_update_grades($data);
    }

    return true;
}

/**
 * Set secondary fields (i.e. fields derived from the form fields)
 * for this HotPot acitivity
 *
 * @param stdclass $data (passed by reference)
 * @param moodle_form $mform
 */
function hotpot_process_formdata(stdclass &$data, $mform) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/hotpot/locallib.php');

    if ($mform->is_add()) {
        $data->timecreated = time();
    } else {
        $data->timemodified = time();
    }

    // get context for this HotPot instance
    $context = get_context_instance(CONTEXT_MODULE, $data->coursemodule);

    $sourcefile = null;
    $data->sourcefile = '';
    $data->sourcetype = '';
    if ($data->sourceitemid) {
        $options = hotpot::sourcefile_options();
        file_save_draft_area_files($data->sourceitemid, $context->id, 'mod_hotpot', 'sourcefile', 0, $options);

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_hotpot', 'sourcefile');

        foreach ($files as $hash => $file) {
            if ($file->get_sortorder()==1) {
                $data->sourcefile = $file->get_filepath().$file->get_filename();
                $data->sourcetype = hotpot::get_sourcetype($file);
                $sourcefile = $file;
                break;
            }
        }
        unset($fs, $files, $file, $hash, $options);
    }

    if (is_null($sourcefile) || $data->sourcefile=='' || $data->sourcetype=='') {
        // sourcefile was missing or not a recognized type - shouldn't happen !!
    }

    // process text fields that may come from source file
    $source = false;
    $textfields = array('name', 'entrytext', 'exittext');
    foreach($textfields as $textfield) {

        $textsource = $textfield.'source';
        if (! isset($data->$textsource)) {
            $data->$textsource = hotpot::TEXTSOURCE_SPECIFIC;
        }

        switch ($data->$textsource) {
            case hotpot::TEXTSOURCE_FILE:
                if ($data->sourcetype && $sourcefile && empty($source)) {
                    $class = 'hotpot_source_'.$data->sourcetype;
                    $source = new $class($sourcefile, $data->course, $data->sourcelocation);
                }
                $method = 'get_'.$textfield;
                if ($source && method_exists($source, $method)) {
                    $data->$textfield = $source->$method();
                } else {
                    $data->$textfield = '';
                }
                break;
            case hotpot::TEXTSOURCE_FILENAME:
                $data->$textfield = basename($data->sourcefile);
                break;
            case hotpot::TEXTSOURCE_FILEPATH:
                $data->$textfield = str_replace(array('/', '\\'), ' ', $data->sourcefile);
                break;
            case hotpot::TEXTSOURCE_SPECIFIC:
            default:
                if (isset($data->$textfield)) {
                    $data->$textfield = trim($data->$textfield);
                } else {
                    $data->$textfield = $mform->get_original_value($textfield, '');
                }
        }

        // default activity name is simply "HotPot"
        if ($textfield=='name' && $data->$textfield=='') {
            $data->$textfield = get_string('modulename', 'hotpot');
        }
    }

    // process entry/exit page settings
    foreach (hotpot::text_page_types() as $type) {

        // show page (boolean switch)
        $pagefield = $type.'page';
        if (! isset($data->$pagefield)) {
            $data->$pagefield = 0;
        }

        // set field names
        $textfield = $type.'text';
        $formatfield = $type.'format';
        $editorfield = $type.'editor';
        $sourcefield = $type.'textsource';
        $optionsfield = $type.'options';

        // ensure text, format and option fields are set
        // (these fields can't be null in the database)
        if (! isset($data->$textfield)) {
            $data->$textfield = $mform->get_original_value($textfield, '');
        }
        if (! isset($data->$formatfield)) {
            $data->$formatfield = $mform->get_original_value($formatfield, FORMAT_HTML);
        }
        if (! isset($data->$optionsfield)) {
            $data->$optionsfield = $mform->get_original_value($optionsfield, 0);
        }

        // set text and format fields
        if ($data->$sourcefield==hotpot::TEXTSOURCE_SPECIFIC) {

            // transfer wysiwyg editor text
            if ($itemid = $data->{$editorfield}['itemid']) {
                if (isset($data->{$editorfield}['text'])) {
                    // get the text that was sent from the browser
                    $editoroptions = hotpot::text_editors_options($context);
                    $text = file_save_draft_area_files($itemid, $context->id, 'mod_hotpot', $type, 0, $editoroptions, $data->{$editorfield}['text']);

                    // remove leading and trailing white space,
                    //  - empty html paragraphs (from IE)
                    //  - and blank lines (from Firefox)
                    $text = preg_replace('/^((<p>\s*<\/p>)|(<br[^>]*>)|\s)+/is', '', $text);
                    $text = preg_replace('/((<p>\s*<\/p>)|(<br[^>]*>)|\s)+$/is', '', $text);

                    $data->$textfield = $text;
                    $data->$formatfield = $data->{$editorfield}['format'];
                }
            }
        }

        // set entry/exit page options
        foreach (hotpot::text_page_options($type) as $name => $mask) {
            $optionfield = $type.'_'.$name;
            if ($data->$pagefield) {
                if (empty($data->$optionfield)) {
                    // disable this option
                    $data->$optionsfield = $data->$optionsfield & ~$mask;
                } else {
                    // enable this option
                    $data->$optionsfield = $data->$optionsfield | $mask;
                }
            }
        }

        // don't show exit page if no content is specified
        if ($type=='exit' && empty($data->$optionsfield) && empty($data->$textfield)) {
            $data->$pagefield = 0;
        }
    }

    // timelimit
    if ($data->timelimit==hotpot::TIME_SPECIFIC) {
        $data->timelimit = $data->timelimitspecific;
    }

    // delay3
    if ($data->delay3==hotpot::TIME_SPECIFIC) {
        $data->delay3 = $data->delay3specific;
    }

    // set stopbutton and stoptext
    if (empty($data->stopbutton_yesno)) {
        $data->stopbutton = hotpot::STOPBUTTON_NONE;
        $data->stoptext = $mform->get_original_value('stoptext', '');
    } else {
        if (! isset($data->stopbutton_type)) {
            $data->stopbutton_type = '';
        }
        if (! isset($data->stopbutton_text)) {
            $data->stopbutton_text = '';
        }
        if ($data->stopbutton_type=='specific') {
            $data->stopbutton = hotpot::STOPBUTTON_SPECIFIC;
            $data->stoptext = $data->stopbutton_text;
        } else {
            $data->stopbutton = hotpot::STOPBUTTON_LANGPACK;
            $data->stoptext = $data->stopbutton_type;
        }
    }

    // save these form settings as user preferences
    $preferences = array();
    foreach (hotpot::user_preferences_fieldnames() as $fieldname) {
        if (isset($data->$fieldname)) {
            $preferences['hotpot_'.$fieldname] = $data->$fieldname;
        }
    }
    set_user_preferences($preferences);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function hotpot_delete_instance($id) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/lib/gradelib.php');

    // check the hotpot $id is valid
    if (! $hotpot = $DB->get_record('hotpot', array('id' => $id))) {
        return false;
    }

    // delete all associated hotpot questions
    $DB->delete_records('hotpot_questions', array('hotpotid' => $id));


    // delete all associated hotpot attempts, details and responses
    if ($attempts = $DB->get_records('hotpot_attempts', array('hotpotid' => $id), '', 'id, id')) {
        $ids = array_keys($attempts);
        $DB->delete_records_list('hotpot_attempts',  'id',        $ids);
        $DB->delete_records_list('hotpot_details',   'attemptid', $ids);
        $DB->delete_records_list('hotpot_responses', 'attemptid', $ids);
    }

    // remove records from the hotpot cache
    $DB->delete_records('hotpot_cache', array('hotpotid' => $hotpot->id));

    // finally remove the hotpot record itself
    $DB->delete_records('hotpot', array('id' => $hotpot->id));

    // gradebook cleanup
    grade_update('mod/hotpot', $hotpot->course, 'mod', 'hotpot', $hotpot->id, 0, null, array('deleted' => true));

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @global object $DB
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $hotpot
 * @return stdclass|null
 */
function hotpot_user_outline($course, $user, $mod, $hotpot) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/hotpot/locallib.php');

    $conditions = array('hotpotid'=>$hotpot->id, 'userid'=>$user->id);
    if (! $attempts = $DB->get_records('hotpot_attempts', $conditions, "timestart ASC", 'id,score,timestart')) {
        return null;
    }

    $time = 0;
    $info = null;

    $scores = array();
    foreach ($attempts as $attempt){
        if ($time==0) {
            $time = $attempt->timestart;
        }
        $scores[] = hotpot::format_score($attempt);
    }
    if (count($scores)) {
        $info = get_string('score', 'hotpot').': '.implode(', ', $scores);
    } else {
        $info = get_string('noactivity', 'hotpot');
    }

    return (object)array('time'=>$time, 'info'=>$info);
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return string HTML
 */
function hotpot_user_complete($course, $user, $mod, $hotpot) {
    $report = hotpot_user_outline($course, $user, $mod, $hotpot);
    if (empty($report)) {
        echo get_string("noactivity", 'hotpot');
    } else {
        $date = userdate($report->time, get_string('strftimerecentfull'));
        echo $report->info.' '.get_string('mostrecently').': '.$date;
    }
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in hotpot activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param stdclass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return boolean
 */
function hotpot_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $DB, $OUTPUT;
    $result = false;

    // the Moodle "logs" table contains the following fields:
    //     time, userid, course, ip, module, cmid, action, url, info

    // this function utilitizes the following index on the log table
    //     log_timcoumodact_ix : time, course, module, action

    // log records are added by the following function in "lib/datalib.php":
    //     add_to_log($courseid, $module, $action, $url='', $info='', $cm=0, $user=0)

    // log records are added by the following HotPot scripts:
    //     (scriptname : log action)
    //     attempt.php : attempt
    //     index.php   : index
    //     report.php  : report
    //     review.php  : review
    //     submit.php  : submit
    //     view.php    : view
    // all these actions have a record in the "log_display" table

    $select = "time>? AND course=? AND module=? AND action IN (?, ?, ?, ?, ?)";
    $params = array($timestart, $course->id, 'hotpot', 'add', 'update', 'view', 'attempt', 'submit');

    if ($logs = $DB->get_records_select('log', $select, $params, 'time ASC')) {

        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        $viewhiddensections = has_capability('moodle/course:viewhiddensections', $coursecontext);

        if ($modinfo = unserialize($course->modinfo)) {
            $coursemoduleids = array_keys($modinfo);
        } else {
            $coursemoduleids = array();
        }

        $stats = array();
        foreach ($logs as $log) {
            $cmid = $log->cmid;
            if (! array_key_exists($cmid, $modinfo)) {
                continue; // invalid $cmid - shouldn't happen !!
            }
            if (! $viewhiddensections && ! $modinfo[$cmid]->visible) {
                continue; // coursemodule is hidden from user
            }
            $sortorder = array_search($cmid, $coursemoduleids);
            if (! array_key_exists($sortorder, $stats)) {
                $stats[$sortorder] = (object)array(
                    'name' => format_string(urldecode($modinfo[$cmid]->name)),
                    'cmid' => $cmid, 'add'=>0, 'update'=>0, 'view'=>0, 'attempt'=>0, 'submit'=>0,
                    'viewreport' => has_capability('mod/hotpot:viewreport', get_context_instance(CONTEXT_MODULE, $cmid)),
                    'users' => array()
                );
            }
            $action = $log->action;
            switch ($action) {
                case 'add':
                case 'update':
                    // store most recent time
                    $stats[$sortorder]->$action = $log->time;
                    break;
                case 'view':
                case 'attempt':
                case 'submit':
                    // increment counter
                    $stats[$sortorder]->$action ++;
                    break;
            }
            $stats[$sortorder]->users[$log->userid] = true;
        }

        $strusers     = get_string('users');
        $stradded     = get_string('added',    'hotpot');
        $strupdated   = get_string('updated',  'hotpot');
        $strviews     = get_string('views',    'hotpot');
        $strattempts  = get_string('attempts', 'hotpot');
        $strsubmits   = get_string('submits',  'hotpot');

        $print_headline = true;
        ksort($stats);
        foreach ($stats as $stat) {
            $li = array();
            if ($stat->add) {
                $li[] = $stradded.': '.userdate($stat->add);
            }
            if ($stat->update) {
                $li[] = $strupdated.': '.userdate($stat->update);
            }
            if ($stat->viewreport) {
                // link to a detailed report of recent activity for this hotpot
                $url = new moodle_url(
                    '/course/recent.php',
                    array('id'=>$course->id, 'modid'=>$stat->cmid, 'date'=>$timestart)
                );
                if ($count = count($stat->users)) {
                    $li[] = $strusers.': '.html_writer::link($url, $count);
                }
                if ($stat->view) {
                    $li[] = $strviews.': '.html_writer::link($url, $stat->view);
                }
                if ($stat->attempt) {
                    $li[] = $strattempts.': '.html_writer::link($url, $stat->attempt);
                }
                if ($stat->submit) {
                    $li[] = $strsubmits.': '.html_writer::link($url, $stat->submit);
                }
            }
            if (count($li)) {
                if ($print_headline) {
                    $print_headline = false;
                    echo $OUTPUT->heading(get_string('modulenameplural', 'hotpot').':', 3);
                }

                $url = new moodle_url('/mod/hotpot/view.php', array('id'=>$stat->cmid));
                $link = html_writer::link($url, format_string($stat->name));

                $text = html_writer::tag('p', $link).html_writer::alist($li);
                echo html_writer::tag('div', $text, array('class'=>'hotpotrecentactivity'));

                $result = true;
            }
        }
    }
    return $result;
}

/**
 * Returns all activity in course hotpots since a given time
 * This function  returns activity for all hotpots since a given time.
 * It is initiated from the "Full report of recent activity" link in the "Recent Activity" block.
 * Using the "Advanced Search" page (cousre/recent.php?id=99&advancedfilter=1),
 * results may be restricted to a particular course module, user or group
 *
 * This function is called from: {@link course/recent.php}
 *
 * @param array(object) $activities sequentially indexed array of course module objects
 * @param integer $index length of the $activities array
 * @param integer $timestart start date, as a UNIX date
 * @param integer $courseid id in the "course" table
 * @param integer $coursemoduleid id in the "course_modules" table
 * @param integer $userid id in the "users" table (default = 0)
 * @param integer $groupid id in the "groups" table (default = 0)
 * @return void adds items into $activities and increments $index
 *     for each hotpot attempt, an $activity object is appended
 *     to the $activities array and the $index is incremented
 *     $activity->type : module type (always "hotpot")
 *     $activity->defaultindex : index of this object in the $activities array
 *     $activity->instance : id in the "hotpot" table;
 *     $activity->name : name of this hotpot
 *     $activity->section : section number in which this hotpot appears in the course
 *     $activity->content : array(object) containing information about hotpot attempts to be printed by {@link print_recent_mod_activity()}
 *         $activity->content->attemptid : id in the "hotpot_quiz_attempts" table
 *         $activity->content->attempt : the number of this attempt at this quiz by this user
 *         $activity->content->score : the score for this attempt
 *         $activity->content->timestart : the server time at which this attempt started
 *         $activity->content->timefinish : the server time at which this attempt finished
 *     $activity->user : object containing user information
 *         $activity->user->userid : id in the "user" table
 *         $activity->user->fullname : the full name of the user (see {@link lib/moodlelib.php}::{@link fullname()})
 *         $activity->user->picture : $record->picture;
 *     $activity->timestamp : the time that the content was recorded in the database
 */
function hotpot_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $coursemoduleid=0, $userid=0, $groupid=0) {
    global $CFG, $DB;

    if (! $course = $DB->get_record('course', array('id'=>$courseid))) {
        return; // invalid course id - shouldn't happen !!
    }

    if (! $modinfo = unserialize($course->modinfo)) {
        return; // no activity mods
    }

    $hotpots = array(); // hotpotid => cmid

    foreach (array_keys($modinfo) as $cmid) {
        if ($modinfo[$cmid]->mod=='hotpot' && ($coursemoduleid==0 || $coursemoduleid==$cmid)) {
            // save mapping from hotpotid => coursemoduleid
            $hotpots[$modinfo[$cmid]->id] = $cmid;
            // initialize array of users who have recently attempted this HotPot
            $modinfo[$cmid]->users = array();
        } else {
            // we are not interested in this mod
            unset($modinfo[$cmid]);
        }
    }

    if (empty($hotpots)) {
        return; // no hotpots
    }

    list($filter, $params) = $DB->get_in_or_equal(array_keys($hotpots));
    $duration = '(ha.timemodified - ha.timestart) AS duration';
    $select = 'ha.*, '.$duration.', u.firstname, u.lastname, u.picture, u.imagealt, u.email';
    $from   = "{hotpot_attempts} ha, {user} u";
    $where  = "ha.hotpotid $filter AND ha.userid=u.id";
    $orderby = 'ha.userid, ha.attempt';

    if ($groupid) {
        // restrict search to a users from a particular group
        $from   .= ', {groups_members} gm';
        $where  .= ' AND ha.userid=gm.userid AND gm.id=?';
        $params[] = $groupid;
    }
    if ($userid) {
        // restrict search to a single user
        $where .= ' AND ha.userid=?';
        $params[] = $userid;
    }
    $where .= ' AND ha.timemodified>?';
    $params[] = $timestart;

    if (! $attempts = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $orderby", $params)) {
        return; // no recent attempts at these hotpots
    }

    foreach (array_keys($attempts) as $attemptid) {
        $attempt = &$attempts[$attemptid];

        if (! array_key_exists($attempt->hotpotid, $hotpots)) {
            continue; // invalid hotpotid - shouldn't happen !!
        }
        $cmid = $hotpots[$attempt->hotpotid];

        if (! array_key_exists($cmid, $modinfo)) {
            continue; // invalid cmid - shouldn't happen !!
        }
        $mod = &$modinfo[$cmid];

        $userid = $attempt->userid;
        if (! array_key_exists($userid, $mod->users)) {
            $mod->users[$userid] = (object)array(
                'id' => $userid,
                'userid' => $userid,
                'firstname' => $attempt->firstname,
                'lastname' => $attempt->lastname,
                'fullname' => fullname($attempt),
                'picture' => $attempt->picture,
                'imagealt' => $attempt->imagealt,
                'email' => $attempt->email,
                'attempts' => array()
            );
        }
        // add this attempt by this user at this course module
        $mod->users[$userid]->attempts[$attempt->attempt] = &$attempt;
    }

    foreach (array_keys($modinfo) as $cmid) {
        $mod = &$modinfo[$cmid];
        if (empty($mod->users)) {
            continue;
        }
        // add an activity object for each user's attempts at this hotpot
        foreach (array_keys($mod->users) as $userid) {
            $user = &$mod->users[$userid];

            // get index of last (=most recent) attempt
            $max_unumber = max(array_keys($user->attempts));

            $activities[$index++] = (object)array(
                'type' => 'hotpot',
                'cmid' => $cmid,
                'name' => format_string(urldecode($mod->name)),
                'user' => (object)array(
                    'id' => $user->id,
                    'userid' => $user->userid,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'fullname' => $user->fullname,
                    'picture' => $user->picture,
                    'imagealt' => $user->imagealt,
                    'email' => $user->email
                ),
                'attempts' => $user->attempts,
                'timestamp' => $user->attempts[$max_unumber]->timemodified
            );
        }
    }
}

/**
 * Print single activity item prepared by {@see hotpot_get_recent_mod_activity()}
 *
 * This function is called from: {@link course/recent.php}
 *
 * @param object $activity an object created by {@link get_recent_mod_activity()}
 * @param integer $courseid id in the "course" table
 * @param boolean $detail
 *         true : print a link to the hotpot activity
 *         false : do no print a link to the hotpot activity
 * @param xxx $modnames
 * @param xxx $viewfullnames
 * @return no return value is required
 */
function hotpot_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot.'/mod/hotpot/locallib.php');

    static $dateformat = null;
    if (is_null($dateformat)) {
        $dateformat = get_string('strftimerecentfull');
    }

    $table = new html_table();
    $table->cellpadding = 3;
    $table->cellspacing = 0;

    if ($detail) {
        $row = new html_table_row();

        $cell = new html_table_cell('&nbsp;', array('width'=>15));
        $row->cells[] = $cell;

        // activity icon and link to activity
        $src = $OUTPUT->pix_url('icon', $activity->type);
        $img = html_writer::tag('img', array('src'=>$src, 'class'=>'icon', $alt=>$activity->name));

        // link to activity
        $href = new moodle_url('/mod/hotpot/view.php', array('id' => $activity->cmid));
        $link = html_writer::link($href, $activity->name);

        $cell = new html_table_cell("$img $link");
        $cell->colspan = 6;
        $row->cells[] = $cell;

        $table->data[] = new html_table_row(array(
            new html_table_cell('&nbsp;', array('width'=>15)),
            new html_table_cell("$img $link")
        ));

        $table->data[] = $row;
    }


    $row = new html_table_row();

    // set rowspan to (number of attempts) + 1
    $rowspan = count($activity->attempts) + 1;

    $cell = new html_table_cell('&nbsp;', array('width'=>15));
    $cell->rowspan = $rowspan;
    $row->cells[] = $cell;

    $picture = $OUTPUT->user_picture($activity->user, array('courseid'=>$courseid));
    $cell = new html_table_cell($picture, array('width'=>35, 'valign'=>'top', 'class'=>'forumpostpicture'));
    $cell->rowspan = $rowspan;
    $row->cells[] = $cell;

    $href = new moodle_url('/user/view.php', array('id'=>$activity->user->userid, 'course'=>$courseid));
    $cell = new html_table_cell(html_writer::link($href, $activity->user->fullname));
    $cell->colspan = 5;
    $row->cells[] = $cell;

    $table->data[] = $row;

    foreach ($activity->attempts as $attempt) {
        if ($attempt->duration) {
            $duration = '('.hotpot::format_time($attempt->duration).')';
        } else {
            $duration = '&nbsp;';
        }

        $href = new moodle_url('/mod/hotpot/review.php', array('id'=>$attempt->id));
        $link = html_writer::link($href, userdate($attempt->timemodified, $dateformat));

        $table->data[] = new html_table_row(array(
            new html_table_cell($attempt->attempt),
            new html_table_cell($attempt->score.'%'),
            new html_table_cell(hotpot::format_status($attempt->status, true)),
            new html_table_cell($link),
            new html_table_cell($duration)
        ));
    }

    echo html_writer::table($table);
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function hotpot_cron () {
    return true;
}

/**
 * Returns an array of user ids who are participanting in this hotpot
 *
 * @param int $hotpotid ID of an instance of this module
 * @return array of user ids, empty if there are no participants
 */
function hotpot_get_participants($hotpotid) {
    global $DB;

    $select = 'DISTINCT u.id, u.id';
    $from   = '{user} u, {hotpot_attempts} a';
    $where  = 'u.id=a.userid AND a.hotpot=?';
    $params = array($hotpotid);

    return $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params);
}

/**
 * Is a given scale used by the instance of hotpot?
 *
 * The function asks all installed grading strategy subplugins. The hotpot
 * core itself does not use scales. Both grade for submission and grade for
 * assessments do not use scales.
 *
 * @param int $hotpotid id of hotpot instance
 * @param int $scaleid id of the scale to check
 * @return bool
 */
function hotpot_scale_used($hotpotid, $scaleid) {
}

/**
 * Is a given scale used by any instance of hotpot?
 *
 * The function asks all installed grading strategy subplugins. The hotpot
 * core itself does not use scales. Both grade for submission and grade for
 * assessments do not use scales.
 *
 * @param int $scaleid id of the scale to check
 * @return bool
 */
function hotpot_scale_used_anywhere($scaleid) {
}

/**
 * Returns all other caps used in the module
 *
 * @return array
 */
function hotpot_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Creates or updates grade items for the give hotpot instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php.
 * Also used by {@link hotpot_update_grades()}.
 *
 * @param stdclass $hotpot instance object with extra cmidnumber and modname property
 * @return void
 */
function hotpot_grade_item_update($hotpot, $grades=null) {
    global $CFG;
    require_once($CFG->dirroot.'/lib/gradelib.php');

    // sanity check on $hotpot->id
    if (! isset($hotpot->id)) {
        return;
    }

    $item = array(
        'itemname' => $hotpot->name
    );
    if (property_exists($hotpot, 'cmidnumber')) {
        //cmidnumber may not be always present
        $item['idnumber'] = $hotpot->cmidnumber;
    }
    if ($hotpot->gradeweighting > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $hotpot->gradeweighting;
        $item['grademin']  = 0;

    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    return grade_update('mod/hotpot', $hotpot->course, 'mod', 'hotpot', $hotpot->id, 0, $grades, $item);
}

/**
 * Update hotpot grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdclass $hotpot instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function hotpot_update_grades($hotpot, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/lib/gradelib.php');

    // sanity check on $hotpot->id
    if (! isset($hotpot->id)) {
        return;
    }

    if ($hotpot->grademethod==hotpot::GRADEMETHOD_AVERAGE || $hotpot->gradeweighting<100) {
        $precision = 1;
    } else {
        $precision = 0;
    }
    $weighting = $hotpot->gradeweighting / 100;

    // set the SQL string to determine the $grade
    switch ($hotpot->grademethod) {
        case hotpot::GRADEMETHOD_HIGHEST:
            $gradefield = "ROUND(MAX(score) * $weighting, $precision) AS grade";
            break;
        case hotpot::GRADEMETHOD_AVERAGE:
            // the 'AVG' function skips abandoned quizzes, so use SUM(score)/COUNT(id)
            $gradefield = "ROUND(SUM(score)/COUNT(id) * $weighting, $precision) AS grade";
            break;
        case hotpot::GRADEMETHOD_FIRST:
            $gradefield = "ROUND(score * $weighting, $precision)";
            $gradefield = $DB->sql_concat('timestart', "'_'", $gradefield);
            $gradefield = "MIN($gradefield) AS grade";
            break;
        case hotpot::GRADEMETHOD_LAST:
            $gradefield = "ROUND(score * $weighting, $precision)";
            $gradefield = $DB->sql_concat('timestart', "'_'", $gradefield);
            $gradefield = "MAX($gradefield) AS grade";
            break;
        default:
            return false; // shouldn't happen !!
    }

    $select = 'timefinish>0 AND hotpotid= ?';
    $params = array($hotpot->id);
    if ($userid) {
        $select .= ' AND userid = ?';
        $params[] = $userid;
    }
    $sql = "SELECT userid, $gradefield FROM {hotpot_attempts} WHERE $select GROUP BY userid";

    $grades = array();
    if ($hotpotgrades = $DB->get_records_sql_menu($sql, $params)) {
        foreach ($hotpotgrades as $hotpotuserid => $hotpotgrade) {
            if ($hotpot->grademethod==hotpot::GRADEMETHOD_FIRST || $hotpot->grademethod==hotpot::GRADEMETHOD_LAST) {
                // remove left hand characters in $gradefield (up to and including the underscore)
                $pos = strpos($hotpotgrade, '_') + 1;
                $hotpotgrade = substr($hotpotgrade, $pos);
            }
            $grades[$hotpotuserid] = (object)array('userid'=>$hotpotuserid, 'rawgrade'=>$hotpotgrade);
        }
    }

    if (count($grades)) {
        hotpot_grade_item_update($hotpot, $grades);

    } else if ($userid && $nullifnone) {
        // no grades for this user, but we must force the creation of a "null" grade record
        hotpot_grade_item_update($hotpot, (object)array('userid'=>$userid, 'rawgrade'=>null));

    } else {
        // no grades and no userid
        hotpot_grade_item_update($hotpot->to_stdclass());
    }
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area hotpot_intro for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @return array of [(string)filearea] => (string)description
 */
function hotpot_get_file_areas($course, $cm, $context) {
    return array(
        'sourcefile' => get_string('sourcefile', 'hotpot'),
        'entry' => get_string('entrytext', 'hotpot'),
        'exit' => get_string('exittext', 'hotpot')
    );
}

/**
 * Serves the files from the hotpot file areas
 *
 * Apart from module intro (handled by pluginfile.php automatically), hotpot files may be
 * media inserted into submission content (like images) and submission attachments. For these two,
 * the fileareas hotpot_submission_content and hotpot_submission_attachment are used.
 * The access rights to the files are checked here. The user must be either a peer-reviewer
 * of the submission or have capability ... (todo) to access the submission files.
 * Besides that, areas hotpot_instructauthors and mod_hotpot instructreviewers contain the media
 * embedded using the mod_form.php.
 *
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
function hotpot_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload) {
}

/**
 * File browsing support for hotpot file areas
 *
 * @param stdclass $browser
 * @param stdclass $areas
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return stdclass file_info instance or null if not found
 */
function hotpot_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding hotpot nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the hotpot module instance
 * @param stdclass $course
 * @param stdclass $module
 * @param stdclass $cm
 */
function hotpot_extend_navigation(navigation_node $hotpotnode, stdclass $course, stdclass $module, stdclass $cm) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/hotpot/locallib.php');

    $hotpot = $DB->get_record('hotpot', array('id' => $cm->instance), '*', MUST_EXIST);
    $hotpot = hotpot::create($hotpot, $cm, $course);

    if ($hotpot->can_viewreport()) {
        $icon = new pix_icon('i/report', '');
        $type = navigation_node::TYPE_SETTING;
        foreach ($hotpot->get_report_modes() as $mode) {
            $url = $hotpot->report_url($mode);
            $label = get_string($mode.'report', 'hotpot');
            $hotpotnode->add($label, $url, $type, null, null, $icon);
        }
    }
}

/**
 * Extends the settings navigation with the Hotpot settings

 * This function is called when the context for the page is a hotpot module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $hotpotnode {@link navigation_node}
 */
function hotpot_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $hotpotnode=null) {
}
