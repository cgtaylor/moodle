<?php
class hotpot_mediaplayer_pyg_mp3_player extends hotpot_mediaplayer {
    public $aliases = array('pyg');
    public $playerurl = 'pyg_mp3_player/pyg_mp3_player.swf';
    public $flashvars_mediaurl = 'file';
    public $more_options = array(
        'width' => 180, 'height' => 30, 'my_BackgroundColor' => '0xE6E6FA', 'autolaunch' => 'false'
    );
    public $flashvars = array(
        'my_BackgroundColor' => PARAM_ALPHANUM, 'autolaunch' => PARAM_ALPHANUM
    );
}
?>
