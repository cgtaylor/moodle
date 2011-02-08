<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_countdown_swfWidth', get_string('width','block_countdown'),
                       get_string('width','block_countdown'), 135));
    $settings->add(new admin_setting_configtext('block_countdown_swfHeight', get_string('height','block_countdown'),
                       get_string('height','block_countdown'), 85));
    $settings->add(new admin_setting_configtext('block_countdown_startyear', get_string('startyear','block_countdown'),
                       get_string('startyear','block_countdown'), 2008));
    $settings->add(new admin_setting_configtext('block_countdown_endyear', get_string('endyear','block_countdown'),
                       get_string('endyear','block_countdown'), 2011));
}
