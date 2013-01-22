<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
//$searcher->defaultQuery('-');
echo "<pre>";
$searcher->timestampQuery('addtime', '2012-12-25')->sort(array('addtime' => 'asc'));
print_r($searcher->search(array('page' => 2)));

?>
