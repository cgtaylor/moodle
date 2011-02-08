<?php
/**
 * Progress Bar Block
 * @author Michael de Raadt <deraadt@usq.edu.au>
 */

//------------------------------------------------------------------------------
// Main game class
class block_progress extends block_base {

    //--------------------------------------------------------------------------
    function init() {
        $this->title = get_string('default_title','block_progress');
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->version = 2010042300;
    }

    //--------------------------------------------------------------------------
    function has_config() {
        return true;
    }

    //--------------------------------------------------------------------------
    function preferred_width() {
        // The preferred value is in pixels
        return 190;
    }

    //--------------------------------------------------------------------------
    function specialization() {
        $this->title = isset($this->config->progressTitle)?$this->config->progressTitle:get_string('default_title','block_progress');
    }

    //--------------------------------------------------------------------------
    function instance_allow_multiple() {
        return true;
    }

    //--------------------------------------------------------------------------
    function applicable_formats() {
        return array('course-view' => true);
    }

    //--------------------------------------------------------------------------
    // This is a list block, the footer is used for code that updates the clocks
    function get_content() {

        // Access to settings needed
        global $USER, $COURSE, $CFG;
        $eventArray = array();
        include($CFG->dirroot.'/blocks/progress/common.php');
        include_once($CFG->dirroot.'/blocks/progress/lib.php');
		include_once($CFG->libdir.'/ddllib.php');


        // If content has already been generated, don't waste time generating it again
        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
 
        // Collect up all the events to track progress
        $numEvents = 0;
        $visibleEvents = 0;
        foreach($modules as $module=>$details) {
			$table = new XMLDBTable($module);
			if(table_exists($table)) {
				$events = get_records($module, 'course', $this->instance->pageid, '', 'id, name'.(array_key_exists('defaultTime',$details)?', '.$details['defaultTime'].' as due':''));
				if($events) {
					foreach($events as $event) {
						$monitored = progress_default_value($this->config->{'monitor_'.$module.$event->id});
						if(isset($monitored) && $monitored=='on') {
							$numEvents++;
							$moduleInstance = get_coursemodule_from_instance($module, $event->id, $COURSE->id);
							
							// Check if the user has attempted the module
							if($module == 'forum') {
								$attempted = record_exists_sql('SELECT id FROM '.$CFG->prefix.'forum_posts WHERE userid=\''.$USER->id.'\' AND discussion IN (SELECT id FROM '.$CFG->prefix.'forum_discussions WHERE forum=\''.$event->id.'\')');
							}
							else if($module == 'resource' || $module == 'book' || $module == 'data' || $module == 'glossary' || $module == 'wiki') {
								$attempted = record_exists_select('log','course=\''.$this->instance->pageid.'\' AND module=\''.$module.'\' AND action=\'view\' AND cmid=\''.$moduleInstance->id.'\' AND userid=\''.$USER->id.'\'');
							}
							else if ($module == 'choice' || $module == 'lesson' || $module == 'scorm' || $module == 'chat' ) {
								$attempted = record_exists_select($details['actionTable'],$module.'id=\''.$event->id.'\' AND userid=\''.$USER->id.'\'');
							}
							else {
								$attempted = record_exists_select($details['actionTable'],$module.'=\''.$event->id.'\' AND userid=\''.$USER->id.'\'');
							}

							// Check the time the module is due
							$locked = progress_default_value($this->config->{'locked_'.$module.$event->id});
							if(isset($details['defaultTime']) && $event->due != 0 && (!isset($locked) || $locked=='on')) {
								$expected = progress_default_value($event->due);
							}
							else {
								$day = $this->config->{'day_'.$module.$event->id};
								$month = $this->config->{'month_'.$module.$event->id};
								$year = $this->config->{'year_'.$module.$event->id};
								$hour = $this->config->{'hour_'.$module.$event->id};
								$minute = $this->config->{'minute_'.$module.$event->id};
								$expected = mktime($hour,$minute,0,$month,$day,$year);
							}

							// Check if the module is visible, and if so, keep a record for it
							if($moduleInstance->visible == 1) {
								$visibleEvents++;
								$eventArray[] = array('expected'=>$expected, 'type'=>$module, 'id'=>$event->id, 'name'=>$event->name, 'attempted'=>$attempted, 'moduleID'=>$moduleInstance->id, 'visible'=>$moduleInstance->visible);
							}    
						}
					}
				}
			}
        }

        // Check if any events were found
        if($numEvents == 0) {
            $this->content->text = get_string('no_events_message','block_progress');
        }
        else if($visibleEvents == 0) {
            $this->content->text = get_string('no_visible_events_message','block_progress');
        }
        // Display progress bar
        else {

            // Set up variables
            sort($eventArray); // by first value in each element, which is time due
            $now = time();
            $nowPos = 0;

            // Find where to put now arrow
            while($nowPos<$visibleEvents && $now>$eventArray[$nowPos]['expected']) {
                $nowPos++;
            }

            // Output function to display activity/resource info
            $this->content->text = '
            <script>
                function progress_showInfo (mod, type, id, name, message, dateTime, instanceID) {
                    document.getElementById("progressBarInfo"+instanceID).innerHTML="<a href=\\\''.$CFG->wwwroot.'/mod/"+mod+"/view.php?id="+id+"\\\'><img src=\\\''.$CFG->wwwroot.'/mod/"+mod+"/icon.gif\\\' /> "+name+"</a><br />"+type+" "+message+"<br />'.get_string('time_expected','block_progress').': "+dateTime+"<br />";
                }
            </script>';

            // Start table
            $this->content->text .= '<table class="progressBarProgressTable" cellpadding="0" cellspacing="0">';

            // Place now arrow
            if($this->config->displayNow=='1') {
                $this->content->text .= '<tr>';

                if($nowPos<$visibleEvents/2) {
                    for($i=0; $i<$nowPos; $i++) {
                        $this->content->text .= '<td>&nbsp;</td>';
                    }
                    $this->content->text .= '<td colspan="'.($visibleEvents-$nowPos).'" style="text-align:left;" id="progressBarHeader"><img src="'.$CFG->wwwroot.'/blocks/progress/img/left.gif" />'.get_string('now_indicator','block_progress').'</td>';
                }
                else {
                    $this->content->text .= '<td colspan='.($nowPos).' style="text-align:right;" id="progressBarHeader">'.get_string('now_indicator','block_progress').'<img src="'.$CFG->wwwroot.'/blocks/progress/img/right.gif" /></td>';
                    for($i=$nowPos; $i<$visibleEvents; $i++) {
                        $this->content->text .= '<td>&nbsp;</td>';
                    }
                }
                $this->content->text .= '</tr>';
            }

            // Start progress bar
            $width = 100/$visibleEvents;
            $this->content->text .= '<tr>';
            foreach($eventArray as $event) {
                $this->content->text .= '<td class="progressBarCell" width="'.$width.'%" onclick="document.location=\''.$CFG->wwwroot.'/mod/'.$event['type'].'/view.php?'.'id='.$event['moduleID'].'\';"';
                $this->content->text .= ' onmouseover="progress_showInfo(\''.$event['type'].'\',\''.get_string($event['type'],'block_progress').'\',\''.$event['moduleID'].'\',\''.addSlashes($event['name']).'\',\''.get_string(($event['attempted']?'':'not_').$event['type'].'_'.$modules[$event['type']]['action'],'block_progress').'\',\''.userdate($event['expected'], get_string('date_format','block_progress')).'\',\''.$this->instance->id.'\');"';
                $this->content->text .= ' bgColor="';
                if($event['attempted']) {
                    $this->content->text .= (isset($CFG->blockProgressBarAttemptedColour)?$CFG->blockProgressBarAttemptedColour:$defaultColours['attempted']).'" /><img src="'.$CFG->wwwroot.'/blocks/progress/img/'.(isset($this->config->progressBarIcons) && $this->config->progressBarIcons=='1'?'tick.gif':'blank.gif').'" />';
                }
                else if($event['expected'] < $now) {
                    $this->content->text .= (isset($CFG->blockProgressBarNotAttemptedColour)?$CFG->blockProgressBarNotAttemptedColour:$defaultColours['notAttempted']).'" /><img src="'.$CFG->wwwroot.'/blocks/progress/img/'.(isset($this->config->progressBarIcons) && $this->config->progressBarIcons=='1'?'cross.gif':'blank.gif').'" />';
                }
                else {
                    $this->content->text .= (isset($CFG->blockProgressBarFutureNotAttemptedColour)?$CFG->blockProgressBarFutureNotAttemptedColour:$defaultColours['futureNotAttempted']).'" /><img src="'.$CFG->wwwroot.'/blocks/progress/img/blank.gif" />';
                }
                $this->content->text .= '</a></td>';
            }
            $this->content->text .= '
                </tr>
            </table>
            <div class="progressEventInfo" id="progressBarInfo'.$this->instance->id.'">'.get_string('mouse_over_prompt','block_progress').'</div>
            ';
        }

        return $this->content;
    }
}

?>
