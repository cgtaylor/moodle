<?php
class hotpot_mediaplayer_matbury_mp3_player extends hotpot_mediaplayer {
    public $aliases = array('matbury');
    public $playerurl = 'matbury_mp3_player/matbury_mp3_player.swf';
    public $flashvars_mediaurl = 'mp3url';
    public $more_options = array(
        'width' => 200, 'height' => 18, 'majorversion' => 9, 'build' => 115,
        'timesToPlay' => 1, 'showPlay' => 'true', 'waitToPlay' => 'true'
    );
    public $flashvars = array(
        'timesToPlay' => PARAM_INT, 'showPlay' => PARAM_ALPHANUM, 'waitToPlay' => PARAM_ALPHANUM
    );
}
?>
