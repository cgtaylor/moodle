<?php
class hotpot_mediaplayer_dew_mp3_player extends hotpot_mediaplayer {
    public $aliases = array('dew');
    public $playerurl = 'dew_mp3_player/dewplayer.swf';
    public $querystring_mediaurl = 'mp3';
    public $more_options = array(
        'width' => 200, 'height' => 20, 'flashvars' => '',
        // 'bgcolor' => 'FFFFFF', 'wmode' => 'transparent',
        // 'autostart' => 0, 'autoreplay' => 0, 'showtime' => 0, 'randomplay' => 0, 'nopointer' => 0
    );
}
?>
