<?php

class hotpot_attempt_html_xhtml extends hotpot_attempt_html {

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
        return array('html_xhtml');
    }
}
