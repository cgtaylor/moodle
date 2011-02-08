<?php

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
    }

    require_once($CFG->libdir.'/completionlib.php');

    if (has_capability('coursereport/completion:view', $context)) {
        $completion = new completion_info($course);
        if ($completion->is_enabled() && $completion->has_criteria()) {
            echo '<p>';
<<<<<<< HEAD
            echo '<a href="'.$CFG->wwwroot.'/course/report/completion/index.php?coursetest='.$course->id.'">'.get_string('coursecompletion').'</a>';
=======
            echo '<a href="'.$CFG->wwwroot.'/course/report/completion/index.php?course='.$course->id.'">'.get_string('coursecompletion').'</a>';
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
            echo '</p>';
        }
    }

