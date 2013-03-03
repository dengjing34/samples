<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$categories = Category::getSecondCategories();
$letters = Category::letter();
$areas = Video::area();
$years = Category::year();
$html = '<div>';
if (!empty($categories)) {
    $html .= '<p><a href="' . $_SERVER['PHP_SELF'] . getParams('cid') . '">全部</a> ';
    /* @var $category Category*/
    foreach ($categories as $category) {
        $params = getParams('cid', $category->id);
        $html .= <<<EOT
<a href="{$params}">{$category->cname}</a>   
EOT;
    }
    $html .= '</p>';
}
$html .= '<p><a href="' . $_SERVER['PHP_SELF'] . getParams('letter') . '">全部</a> ';
foreach ($letters as $letter) {
    $params = getParams('letter', $letter);
    $html .= <<<EOT
<a href="{$params}">{$letter}</a>   
EOT;
}
$html .= '</p>';
if (!empty($areas)) {
    $html .= '<p><a href="' . $_SERVER['PHP_SELF'] . getParams('area') . '">全部</a> ';
    /* @var $category Category*/
    foreach ($areas as $area) {
        $areaName = current($area);
        $areaCount = next($area);
        $params = getParams('area', $areaName);
        $html .= <<<EOT
<a href="{$params}" title="共{$areaCount}部视频">{$areaName}</a>   
EOT;
    }
    $html .= '</p>';
}
$html .= '<p><a href="' . $_SERVER['PHP_SELF'] . getParams('year') . '">全部</a> ';
foreach ($years as $year) {
    $params = getParams('year', $year);
    $html .= <<<EOT
<a href="{$params}">{$year}</a>   
EOT;
}
$html .= '</p>';
echo $html;

function getParams($key = null, $val = null) {
    $get = array_filter($_GET);
    if (isset($get[$key])) unset ($get[$key]);
    if ($val) $get[$key] = $val;    
    $result = null;
    if (!empty($get)) {
        $result = '?' . http_build_query($get);        
    }
    return $result;
}
?>
