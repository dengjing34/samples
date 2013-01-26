<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$videoSearcher = new VideoSearcher();
$result = $videoSearcher->facetDateRangeQUery('createdtime', '2013-01-01', '2013-01-02')
        ->facetTimestampRangeQuery('addtime', '2013-01-01', '2013-01-02')
        ->facetField('cid')
        ->facetField(array('language', 'year'))        
        ->facetSearch();
echo '<pre>';
print_r($result);
?>