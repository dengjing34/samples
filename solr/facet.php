<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$videoSearcher = new VideoSearcher();
$result = $videoSearcher->facetQueryDateRange('createdtime', '2013-01-01', '2013-01-02')
        ->facetQueryTimestampRange('addtime', '2013-01-01', '2013-01-02')
        ->facetField('cid')
        ->facetField(array('language', 'year', 'cid'))
        ->facetSort('count')//by default is [count], another allowed value is [index]
        ->facetOffset(0) //by default is 0
        ->facetLimit(10) //by default is 100
        ->facetMincount(0)//must great equal than 0
        ->facetDateQuery('createdtime', '2012-12-30', '', '1DAY')
        ->facetRangeQuery('addtime', strtotime('2012-12-30'), time(), 86400)
        ->facetRangeQuery('hits', 100, 500, 50)
        ->facetSearch();
echo '<pre>';
print_r($result);
?>