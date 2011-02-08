<?php
class date_difference {
    	function date_difference($date, $datee){
        $this->date1 = $date;
        $this->date2 = $datee;
        $this->days = intval((strtotime($this->date1) - strtotime($this->date2)) / 86400);
        $this->a = ((strtotime($this->date1) - strtotime($this->date2))) % 86400;
        $this->hours = intval(($this->a) / 3600);
        $this->a = ($this->a) % 3600;
        $this->minutes = intval(($this->a) / 60);
        $this->a = ($this->a) % 60;
        $this->seconds = $this->a;
    }
}
?>