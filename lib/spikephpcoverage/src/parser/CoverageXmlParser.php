<?php
    /*
<<<<<<< HEAD
    *  $Id: CoverageXmlParser.php,v 1.2 2010/12/14 17:36:00 moodlerobot Exp $
=======
    *  $Id$
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
    *  
    *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Software License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php

    require_once dirname(__FILE__) . "/BasicXmlParser.php";

    /** 
     * Special parser for SpikePHPCoverage data parsing 
     * Expect input in following format:
     * <spike-phpcoverage>
     *   <file path="/complete/file/path">
     *     <line line-number="10" frequency="1"/>
     *     <line line-number="12" frequency="2"/>
     *   </file>
     *   <file path="/another/file/path">
     *     ...
     *   </file>
     * </spike-phpcoverage>
     * 
     * @author Nimish Pachapurkar <npac@spikesource.com>
<<<<<<< HEAD
     * @version $Revision: 1.2 $
=======
     * @version $Revision$
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
     * @package SpikePHPCoverage_Parser
     */
    class CoverageXmlParser extends BasicXmlParser {
        /*{{{ Members */

        protected $_data = array();
        protected $_lastFilePath;

        /*}}}*/
        /*{{{ public function startHandler() */

        public function startHandler($xp, $name, $attrs) {
            switch($name) {
            case "FILE":
                $fileAttributes = $this->handleAttrTag($name, $attrs);
                $this->_lastFilePath = $fileAttributes["PATH"];
                if(!isset($this->_data[$this->_lastFilePath])) {
                    $this->_data[$this->_lastFilePath] = array();
                }
                break;

            case "LINE":
                $lineAttributes = $this->handleAttrTag($name, $attrs);
                $lineNumber = (int)$lineAttributes["LINE-NUMBER"];
                if(!isset($this->_data[$this->_lastFilePath][$lineNumber])) {
                    $this->_data[$this->_lastFilePath][$lineNumber] = (int)$lineAttributes["FREQUENCY"];
                }
                else {
                    $this->_data[$this->_lastFilePath][$lineNumber] += (int)$lineAttributes["FREQUENCY"];
                }
                break;
            }
        }

        /*}}}*/
        /*{{{ public function getCoverageData() */

        /** 
         * Returns coverage data array from the XML
         * Format:
         * Array
         * (
         *   [/php/src/remote/RemoteCoverageRecorder.php] => Array
         *   (
         *     [220] => 1
         *     [221] => 1
         *   )
         *
         *   [/opt/oss/share/apache2/htdocs/web/sample.php] => Array
         *   (
         *     [16] => 1
         *     [18] => 1
         *   )
         * )
         * 
         * @access public
         */
        public function getCoverageData() {
            return $this->_data;
        }

        /*}}}*/
    }
?>