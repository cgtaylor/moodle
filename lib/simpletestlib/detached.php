<?php
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage UnitTester
<<<<<<< HEAD
 *  @version    $Id: detached.php,v 1.5 2010/12/14 17:35:45 moodlerobot Exp $
=======
 *  @version    $Id$
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
 */

/**#@+
 *  include other SimpleTest class files
 */
require_once(dirname(__FILE__) . '/xml.php');
require_once(dirname(__FILE__) . '/shell_tester.php');
/**#@-*/

/**
 *    Runs an XML formated test in a separate process.
 *    @package SimpleTest
 *    @subpackage UnitTester
 */
class DetachedTestCase {
    var $_command;
    var $_dry_command;
    var $_size;

    /**
     *    Sets the location of the remote test.
     *    @param string $command       Test script.
     *    @param string $dry_command   Script for dry run.
     *    @access public
     */
    function DetachedTestCase($command, $dry_command = false) {
        $this->_command = $command;
        $this->_dry_command = $dry_command ? $dry_command : $command;
        $this->_size = false;
    }

    /**
     *    Accessor for the test name for subclasses.
     *    @return string       Name of the test.
     *    @access public
     */
    function getLabel() {
        return $this->_command;
    }

    /**
     *    Runs the top level test for this class. Currently
     *    reads the data as a single chunk. I'll fix this
     *    once I have added iteration to the browser.
     *    @param SimpleReporter $reporter    Target of test results.
     *    @returns boolean                   True if no failures.
     *    @access public
     */
    function run(&$reporter) {
        $shell = new SimpleShell();
        $shell->execute($this->_command);
        $parser = &$this->_createParser($reporter);
        if (! $parser->parse($shell->getOutput())) {
            trigger_error('Cannot parse incoming XML from [' . $this->_command . ']');
            return false;
        }
        return true;
    }

    /**
     *    Accessor for the number of subtests.
     *    @return integer       Number of test cases.
     *    @access public
     */
    function getSize() {
        if ($this->_size === false) {
            $shell = new SimpleShell();
            $shell->execute($this->_dry_command);
            $reporter = new SimpleReporter();
            $parser = &$this->_createParser($reporter);
            if (! $parser->parse($shell->getOutput())) {
                trigger_error('Cannot parse incoming XML from [' . $this->_dry_command . ']');
                return false;
            }
            $this->_size = $reporter->getTestCaseCount();
        }
        return $this->_size;
    }

    /**
     *    Creates the XML parser.
     *    @param SimpleReporter $reporter    Target of test results.
     *    @return SimpleTestXmlListener      XML reader.
     *    @access protected
     */
    function &_createParser(&$reporter) {
        return new SimpleTestXmlParser($reporter);
    }
}
?>