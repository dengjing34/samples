<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
$searcher->timestampRangeQuery('addtime', '2012-12-12', '2013-01-16 23:55:55');
$searcher->dayQuery('addtime', '2013-01-06');
print_r($searcher->query);

?>
