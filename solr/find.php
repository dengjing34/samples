<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$q = isset($_GET['q']) ? $_GET['q'] : null;
$result = array();
if (!is_null($q)) {
    $videoSearcher = new VideoSearcher();
    $result = $videoSearcher->defaultQuery($q)->sort(array('id' => 'desc'))->search();
}
$html = <<<EOT
<form>
    <a href="video_add.php">新增</a><br />
    关键字:<input type="text" name="q" value="{$q}" /><button type="submit">查询</button>
</form>
EOT;
/* @var $o Video */
if (!empty($result)) {
    foreach ($result['docs'] as $o) {
        $html .= <<<EOT
    <a href="video_modify.php?id={$o->id}">{$o->title}</a><br />    
EOT;
    }    
}   

echo $html;
var_dump($result, MysqlData::$counter);
?>
