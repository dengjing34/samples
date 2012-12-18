<?php
$filter = array(
    'city' => array(
        'chengdu' => '成都',
        'beijing' => '北京',
        'shanghai' => '上海',
        'guangzhou' => '广州',
    ),
    'brand' => array(
        'apple' => '苹果',
        'ibm' => 'ibm',
        'lenovo' => '联想',
        'sumsung' => '三星',
    ),
    'price' => array(
        'lv1' => '1000~1999',
        'lv2' => '2000~2999',
        'lv3' => '3000~3999',
        'lv4' => '4000~4999',
    ),
    'os' => array(
        'android' => '安卓',
        'mac' => '苹果',
        'blackberry' => '黑莓',
        'Symbian' => '塞班',
    ),
);
$html = array();
foreach ($filter as $filterName => $eachFilter) {
    $params = filterParams($filterName);
    $html[] = "<div><a href=\"{$params}\">全部</a>";
    foreach ($eachFilter as $key => $val) {       
        $params = filterParams($filterName, array($filterName => $key));
        $className = isset($_GET[$filterName]) && $_GET[$filterName] == $key ? ' class="current"' : null;
        $html[] = " <a href=\"{$params}\"{$className}>{$val}</a> ";
    }
    $html[] = "</div>";
}
echo join("\n", $html);

function filterParams($except = null, $addition = array()) {
    $effectParams = array_filter($_GET);
    if (isset($effectParams[$except])) unset ($effectParams[$except]);    
    $queryString = http_build_query($addition + $effectParams);    
    return empty($queryString) ? $_SERVER['SCRIPT_NAME'] : "?{$queryString}";
}
?>
<style>
 a.current{color:red;}
</style>