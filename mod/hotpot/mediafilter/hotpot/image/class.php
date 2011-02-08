<?php
class hotpot_mediaplayer_image extends hotpot_mediaplayer {
    public $aliases = array('img');
    public $media_filetypes = array('gif','jpg','png');
    public $options = array(
        'width' => 0, 'height' => 0, 'build' => 0,
        'quality' => '', 'majorversion' => '', 'flashvars' => ''
    );
    public $spantext = '';

    /**
     * generate
     *
     * @param xxx $filetype
     * @param xxx $link
     * @param xxx $mediaurl
     * @param xxx $options
     * @return xxx
     */
    function generate($filetype, $link, $mediaurl, $options)  {
        $img = '<img src="'.$mediaurl.'"';
        if (array_key_exists('player', $options)) {
            unset($options['player']);
        }
        if (! array_key_exists('alt', $options)) {
            $options['alt'] = basename($mediaurl);
        }
        foreach ($options as $name => $value) {
            if ($value) {
                $img .= ' '.$name.'="'.$value.'"';
            }
        }
        $img .= ' />';
        return $img;
    }
}
