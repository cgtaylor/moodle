Description of Spike PHPCoverage 0.8.2 library import into Moodle

Removed:
 * build.xml
 * samples
 * screenshots
 * xdebug_bin
 * src/PEAR.php | => Already in lib/pear
 * src/XML      |

Added:
 * index.html - prevent directory browsing on misconfigured servers
 * readme_moodle.txt - this file ;-)

Our changes: /// Look for "moodle" in code
 * src/parser/PHPParser.php - comment some debug lines causing some notices in moodle
 * src/parser/PHPParser.php - added support for the T_ABSTRACT token
 * src/reporter/HtmlCoverageReporter.php, src/reporter/html/indexheader.html,
   src/reporter/html/header.html, src/reporter/html/footer.html - various xhtml fixes
 * removed deprecated "=& new"
<<<<<<< HEAD
=======
 * src/phpcoverage.remote.bottom.inc.php | => Prevent execution (not used and unsecure)
   src/phpcoverage.remote.top.inc.php    |
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf

20090621 - Eloy Lafuente (stronk7): Original import of 0.8.2 release
