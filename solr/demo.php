<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
echo $searcher->query();

?>
