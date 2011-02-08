<?php //$Id: block_countdown.php,v 1.8 2005/05/19 20:09:57 defacer Exp $

class block_countdown extends block_base {

    function init() {
        $this->title = get_string('countdown', 'block_countdown');
        $this->version = 2008050100;
    }

    function applicable_formats() {
        return array('all' => true);
    }
	
	function has_config() {
    	return true;
	}
		
    function specialization() {
        $this->title = isset($this->config->title) ? $this->config->title : get_string('newcountdownblock', 'block_countdown');
    }

    function instance_allow_multiple() {
        return true;
    }
    
    function hide_header() {
        if (($this->config->showheader == 0)&&($this->config->title=="")) {
            return true;
        }
    }

    function get_content() {
		global $CFG;
        if ($this->content !== NULL) {
            return $this->content;
        }
//Added to convert to correct to file name when set as the Select box on the config page uses the array key index rather than a text value - Carl Taylor
if (isset($this->config->clockStyle)) {
	$temp = $this->config->clockStyle;
	switch ($temp){
		Case "0":
		$this->config->clockStyle = "default.swf";
		break;

		Case "1":
		$this->config->clockStyle = "one.swf";
		break;

		Case "2":
		$this->config->clockStyle = "seconds.swf";
		break;
	}
}


    	if($this->config->header==""){$this->config->showheader = 0;}
        require_once('lib/date_difference_class.php');
		if ($this->config->yr=="annual"){$yearVal="2010";}
		else {$yearVal = $this->config->yr;}
        $result = new date_difference($yearVal.'-'.$this->config->mo.'-'.$this->config->da.' '.$this->config->ho.':'.$this->config->mi.':00', date('Y:m:d H:i:s'));
		if((($result->days <= 0) && ($result->hours <= 0) && ($result->minutes <= 0) && ($result->seconds <= 0))&&($this->config->finish=="")) {
            $content = '';
            $this->config->showheader = 0;
        } else {
		$width=135;
		$height=85;
		if (isset($CFG->block_countdown_swfHeight)) {
            $height = $CFG->block_countdown_swfHeight;
        }
		if (isset($CFG->block_countdown_swfWidth)) {
            $width = $CFG->block_countdown_swfWidth;
        }

		if (!isset($this->config->clockStyle)) {
        $this->config->clockStyle = "default.swf";
		}		
        $filteropt = new stdClass;
        $filteropt->noclean = true;
        $this->content = new stdClass;
        $this->content->text = isset($this->config->text1) ? format_text($this->config->text1, FORMAT_HTML, $filteropt) : '';
        if (!isset($this->config->clockStyle)){
		$this->context->text .= "Please configure this block";
		}else {
		$this->content->text .= "<center><object wmode='transparent' width='$width' height='$height' data='$CFG->wwwroot/blocks/".$this->name()."/clocks/".$this->config->clockStyle."?";
		if ($this->config->yr<>"annual"	){$this->content->text .= "yr=".$this->config->yr."&amp;";}
		$this->content->text .="mo=".$this->config->mo."&amp;da=".$this->config->da."&amp;ho=".$this->config->ho."&amp;mi=".$this->config->mi."&amp;co=".$this->config->digitcolor."&amp;co2=".$this->config->textcolor."&amp;dayText=".get_string('days', 'block_countdown')."&amp;minutesText=".get_string('minutes', 'block_countdown')."&amp;hoursText=".get_string('hours', 'block_countdown')."&amp;secondsText=".get_string('seconds', 'block_countdown')."&amp;message=".urlencode($this->config->finish);
		if ($this->config->servertime=="server"){$this->content->text .="&amp;sc=$CFG->wwwroot/blocks/".$this->name()."/gettime.php";}
		$this->content->text .= "' type='application/x-shockwave-flash'><param name='movie' value='$CFG->wwwroot/blocks/".$this->name()."/clocks/".$this->config->clockStyle."?";
		if ($this->config->yr<>"annual"	){$this->content->text .= "yr=".$this->config->yr."&amp;";}
		$this->content->text .= "mo=".$this->config->mo."&amp;da=".$this->config->da."&amp;ho=".$this->config->ho."&amp;mi=".$this->config->mi."&amp;co=".$this->config->digitcolor."&amp;co2=".$this->config->textcolor."&amp;dayText=".get_string('days', 'block_countdown')."&amp;minutesText=".get_string('minutes', 'block_countdown')."&amp;hoursText=".get_string('hours', 'block_countdown')."&amp;secondsText=".get_string('seconds', 'block_countdown')."&amp;message=".urlencode($this->config->finish);
		if ($this->config->servertime=="server"){$this->content->text .="&amp;sc=$CFG->wwwroot/blocks/".$this->name()."/gettime.php";}
		$this->content->text .= "' />";
		$this->content->text .= "<param name='bgcolor' value='#".$this->config->bgcolor."' /><param name='wmode' value='transparent'/>";		   
		$this->content->text.= "</object></center>";
		}
        $this->content->text .= isset($this->config->text2) ? format_text($this->config->text2, FORMAT_HTML, $filteropt) : '';		
		$this->content->footer = '';

        unset($filteropt); // memory footprint
        }

        return $this->content;
    }
}
?>
