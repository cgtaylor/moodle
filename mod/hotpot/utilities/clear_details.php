<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_login(SITEID);
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM, SITEID), $USER->id);

// $SCRIPT is set by initialise_fullme() in "lib/setuplib.php"
// it is the path below $CFG->wwwroot of this script
$PAGE->set_url($CFG->wwwroot.$SCRIPT);

$title = get_string('cleardetails', 'hotpot');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

if ($confirm = optional_param('confirm', 0, PARAM_INT)) {
    $DB->delete_records('hotpot_details');
    $count_details = 0;
} else {
    $count_details = $DB->count_records('hotpot_details');
}
$count_quizzes = $DB->count_records('hotpot');

echo $OUTPUT->box_start();

echo '<table style="margin:auto"><tbody>'."\n";
echo '<tr><th style="text-align:right;">'.get_string('quizzes', 'hotpot').':</th><td>'.$count_quizzes.'</td></tr>'."\n";
echo '<tr><th style="text-align:right;">'.get_string('detailsrecords', 'hotpot').':</th><td>'.$count_details.'</td></tr>'."\n";
if ($count_details) {
    echo '<tr><td colspan="2" style="text-align:center;">';
    echo '<form action="'.$CFG->wwwroot.$SCRIPT.'" method="post">';
    echo '<fieldset>';
    echo '<input type="hidden" value="1" name="confirm" />';
    echo '<input type="submit" value="'.get_string('confirm').'" />';
    echo '</fieldset>';
    echo '</td></tr>'."\n";
} else {
    echo '<tr><td colspan="2" style="text-align:center;">'.get_string('cleareddetails', 'hotpot').'</td></tr>'."\n";
}
echo '</tbody></table>'."\n";

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
