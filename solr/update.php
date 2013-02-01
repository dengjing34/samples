<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');    
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
$data = array('id' => 20000, 'cid' => '2', 'title' => '20000\'stitle');
if (!$searcher->update($data)) {
    print_r($searcher->lastError());
}

?>
