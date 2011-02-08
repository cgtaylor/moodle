<?php
// prevent caching
header("Expires: Sat, 25 Aug 2000 23:23:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0"); 
header("Pragma: no-cache");
echo'timeonserver='.mktime();
?>