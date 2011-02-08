<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_login(SITEID);
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM, SITEID), $USER->id);

// $SCRIPT is set by initialise_fullme() in "lib/setuplib.php"
// it is the path below $CFG->wwwroot of this script
$PAGE->set_url($CFG->wwwroot.$SCRIPT);

// set title
$title = 'HotPot Utilities index';
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->box_start();

// get path to this directory
$dirname = dirname($SCRIPT);
$dirpath = $CFG->dirroot.'/'.$dirname;

echo html_writer::start_tag('ul')."\n";

$items = new DirectoryIterator($dirpath);
foreach ($items as $item) {
    if ($item->isDot() || substr($item, 0, 1)=='.' || $item=='index.php') {
        continue;
    }
    if ($item->isFile()) {
        $href = $CFG->wwwroot.'/'.$dirname.'/'.$item;
        echo html_writer::tag('li', html_writer::tag('a', $item, array('href' => $href)))."\n";
    }
}

echo html_writer::end_tag('ul')."\n";

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
