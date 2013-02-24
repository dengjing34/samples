<?php
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$video = new Video();
//$video->load(20462)->delete();
//die;
$columns = array_keys(array_diff_key($video->columns(), array_flip(array($video->key()))));
if ($_POST) {
    foreach ($columns as $property) {        
        if (isset($_POST[$property]) && strlen($_POST[$property]) > 0) {
            $video->{$property} = $_POST[$property];
        }
    }
    try {
        $video->save();
        header("Location: video_modify.php?id={$video->id}");
    } catch (Exception $e) {
        die($e->getMessage());
    }    
}
$html = '<button type="button" onclick="history.go(-1);">返回</button><form method="post">';
foreach ($columns as $property) {    
    if ($property == 'content') {
        $html .= "<textarea name=\"content\" style=\"width:800px;height:200px;\"></textarea><br />";
        continue;
    }
    $html .= <<<EOT
{$property}:<input type="text" name="{$property}" /><br />
EOT;
}
$html .= '<button type="submit">保存</button></form>';
echo $html;
?>
