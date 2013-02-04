<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');    
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
if ($_POST) {
    if (!$searcher->update($_POST)) {
        echo '<pre>';
        print_r($searcher->lastError());
        die;
    }
    echo "insert successed <a href=\"{$_SERVER['HTTP_REFERER']}\">back</a> or <a href=\"update.php?id={$_POST['id']}\">view it</a>";
    die;
}
$schema = $searcher->schema();
$html = '<form method="post">';
if (isset($schema['schema']['fields'])) {
    foreach (array_keys($schema['schema']['fields']) as $field) {
        if ($field == '_version_') continue;
        $html .= <<<EOT
        {$field}:<input type="text" name="{$field}" /><br />
EOT;
    }
}
$html .= '<button type="submit">添加</button></form>';
echo $html;
?>
