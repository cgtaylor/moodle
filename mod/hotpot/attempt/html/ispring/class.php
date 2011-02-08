<?php
class hotpot_attempt_html_ispring extends hotpot_attempt_html {

    /**
     * init
     *
     * @param xxx $hotpot (passed by reference)
     */
    function init($hotpot)  {
        parent::init($hotpot);
    }

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    public static function sourcetypes()  {
        return array('html_ispring');
    }

    /**
     * preprocessing
     *
     * @return xxx
     */
    function preprocessing()  {
        if ($this->cache_uptodate) {
            return true;
        }

        if (! $this->hotpot->source->get_filecontents()) {
            // empty source file - shouldn't happen !!
            return false;
        }

        // remove doctype
        $search = '/\s*(?:<!--\s*)?<!DOCTYPE[^>]*>\s*(?:-->\s*)?/s';
        $this->hotpot->source->filecontents = preg_replace($search, '', $this->hotpot->source->filecontents);

        // replace <object> with link and force through filters
        $search = '/<object id="presentation"[^>]*>.*?<param name="movie" value="([^">]*)"[^>]*>.*?<\/object>/is';
        $replace = '<a href="\\1?d=800x600">\\1</a>';
        $this->hotpot->source->filecontents = preg_replace($search, $replace, $this->hotpot->source->filecontents);

        // remove fixprompt.js
        $search = '/<script[^>]*src="[^">]*fixprompt.js"[^>]*(?:(?:\/>)|(?:<\/script>))\s*/s';
        $this->hotpot->source->filecontents = preg_replace($search, '', $this->hotpot->source->filecontents);

        parent::preprocessing();
    }
}
