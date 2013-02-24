<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? $_GET['id'] : null;
if ($id) {
    $video = new Video();
    $video->id = $id;
    try {
        $video->load();
        if ($_POST) {
            foreach (array_keys($video->columns()) as $property) {
                if (isset($_POST[$property]) && $_POST[$property]) {
                    $video->{$property} = $_POST[$property];
                }
            }
            $video->save();
            header("Location: {$_SERVER['HTTP_REFERER']}");
            exit;
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    $html = '<form method="post"><button type="button" onclick="history.go(-1);">返回</button><br />';
    foreach (array_keys($video->columns()) as $property) {
        if ($property == 'content') {
            $html .= <<<EOT
<textarea name="content" style="width:800px;height:200px;">{$video->content}</textarea><br />   
EOT;
            continue;
        }
        $html .= <<<EOT
{$property}:<input type="text" name="{$property}" value="{$video->{$property}}" /><br />
EOT;
    }
    $html .= '<button type="submit">更新</button></form>';
}
echo $html;
var_dump(MysqlData::$counter);
?>
