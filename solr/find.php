<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$video = new Video();
$video->load(1);
$cache = new Cache();
$cache->increment('counter');
//$cache->set('video', $video, 10);

echo '<pre>';
var_dump($cache->get('counter'));
?>
