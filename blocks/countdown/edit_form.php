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
 * Form for editing Mentees block instances.
 *
 * @package   moodlecore
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing Mentees block instances.
 *
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_countdown_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG, $DB, $USER;

        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_countdown'));
        $mform->setType('config_title', PARAM_MULTILANG);

	  $attributes=array('server','user');
        $mform->addElement('select', 'config_servertime', get_string('configtimesource', 'block_countdown'), $attributes);

	  //Attempting to display the clock for viewing but got bored and confused - Carl Taylor
	  //$display = "<embed src=\""."$CFG->wwwroot/blocks/clocks/default.swf"."\"/>";
	  //$mform->addElement('html',$display);
	  
	  $attributes=array('default.swf','one.swf','seconds.swf');
        $mform->addElement('select', 'config_clockStyle', get_string('clock', 'block_countdown'), $attributes);

	  //Couldnt get working wasnt binding the actual value so used the above - Carl Taylor
	  //$radioarray=array();
	  //$radioarray[] = &MoodleQuickForm::createElement('radio', 'servertime', '', get_string('server','block_countdown'), 'server');
	  //$radioarray[] = &MoodleQuickForm::createElement('radio', 'servertime', '', get_string('usercomputer','block_countdown'), 'user');
	  //$mform->addGroup($radioarray, 'config_servertime', 'Time Source', array(' '), false);

	  //Would like to use the date_time_selector but cant concat time string to pass to it - Carl Taylor
        $mform->addElement('text', 'config_da', get_string('configdate', 'block_countdown'));
        $mform->setType('config_date', PARAM_INT);

        $mform->addElement('text', 'config_mo', get_string('configmonth', 'block_countdown'));
        $mform->setType('config_month', PARAM_INT);

        $mform->addElement('text', 'config_yr', get_string('configyear', 'block_countdown'));
        $mform->setType('config_year', PARAM_INT);

        $mform->addElement('text', 'config_ho', get_string('confighour', 'block_countdown'));
        $mform->setType('config_hour', PARAM_INT);

        $mform->addElement('text', 'config_mi', get_string('configminute', 'block_countdown'));
        $mform->setType('config_minute', PARAM_INT);

        $mform->addElement('htmleditor', 'config_text1', get_string('configcontentabove', 'block_countdown'));
        $mform->setType('configcontentabove', PARAM_MULTILANG);

        $mform->addElement('htmleditor', 'config_finish', get_string('finishtext', 'block_countdown'));
        $mform->setType('configcontentbelow', PARAM_MULTILANG);

        $mform->addElement('htmleditor', 'config_text2', get_string('configcontentbelow', 'block_countdown'));
        $mform->setType('finish_text', PARAM_MULTILANG);
    }
}