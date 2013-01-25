<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
//$searcher->defaultQuery('-');
echo "<pre>";
$searcher->setRows(10)
        ->dateRangeQuery('createdtime', '2012-01-01', '2013-01-01')
        ->defaultQuery('周星驰')
        ->setPage(1)
        ->setFieldList(array('title', 'createdtime', 'addtime', 'content', 'year', 'actor'))
        ->sort(array('addtime' => 'desc'));
print_r($searcher->search());

?>
