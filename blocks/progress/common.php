<?php

// Activies that can be monitored (resource is a special case)
$modules = array(
    'assignment' => array('defaultTime'=>'timedue','actionTable'=>'assignment_submissions','action'=>'submitted'),
	'book' => array('action'=>'viewed'),
    'chat' => array('actionTable'=>'chat_messages','action'=>'posted_to'),
    'choice' => array('defaultTime'=>'timeclose','actionTable'=>'choice_answers','action'=>'answered'),
    'data' => array('defaultTime'=>'timeviewto','action'=>'viewed'),
    'feedback' => array('defaultTime'=>'timeclose','actionTable'=>'feedback_completed','action'=>'responded_to'),
    'forum' => array('defaultTime'=>'assesstimefinish','action'=>'posted_to'),
    'glossary' => array('action'=>'viewed'),
    'lesson' => array('defaultTime'=>'deadline','actionTable'=>'lesson_attempts','action'=>'attempted'),
    'quiz' => array('defaultTime'=>'timeclose','actionTable'=>'quiz_attempts','action'=>'attempted'),
    'resource' => array('action'=>'viewed'),
    'scorm' => array('actionTable'=>'scorm_scoes_track','action'=>'attempted'),
    'wiki' => array('action'=>'viewed')
);

// Types of resources that can be monitored
$resourcesMonitorable = array (
    'directory',
    'text',
    'html',
    'file' // or link
);

// Default colours that can be overridden at the site level
$defaultColours = array(
    'attempted'=>'#33CC00',
    'notAttempted'=>'#FF3300',
    'futureNotAttempted'=>'#3366FF'
);

?>