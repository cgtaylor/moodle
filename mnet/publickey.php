<?php
/**
 * Print this server's public key and exit
 *
 * @author  Donal McMullan  donal@catalyst.net.nz
 * @version 0.0.1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mnet
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once $CFG->dirroot.'/mnet/lib.php';

if ($CFG->mnet_dispatcher_mode === 'off') {
    print_error('mnetdisabled', 'mnet');
}

<<<<<<< HEAD
header("Content-type: text/plain");
=======
header("Content-type: text/plain; charset=utf-8");
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
$keypair = mnet_get_keypair();
echo $keypair['certificate'];
