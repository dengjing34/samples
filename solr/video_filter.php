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
    $html .= '<p><a href="">全部</a> ';
    /* @var $category Category*/
    foreach ($categories as $category) {
        $html .= <<<EOT
<a href="?cid={$category->id}">{$category->cname}</a>   
EOT;
    }
    $html .= '</p>';
}
$html .= '<p><a href="">全部</a> ';
foreach ($letters as $letter) {
    $html .= <<<EOT
<a href="?letter={$letter}">{$letter}</a>   
EOT;
}
$html .= '</p>';
if (!empty($areas)) {
    $html .= '<p><a href="">全部</a> ';
    /* @var $category Category*/
    foreach ($areas as $area) {
        $areaName = current($area);
        $areaCount = next($area);
        $html .= <<<EOT
<a href="?area={$areaName}" title="共{$areaCount}部视频">{$areaName}</a>   
EOT;
    }
    $html .= '</p>';
}
$html .= '<p><a href="">全部</a> ';
foreach ($years as $year) {
    $html .= <<<EOT
<a href="?year={$year}">{$year}</a>   
EOT;
}
$html .= '</p>';
echo $html;
?>
