<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');    
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!empty($_POST)) {
    if (!$searcher->update($_POST)) {
        echo "<pre>";
        print_r($searcher->lastError());
    } else {
        header("Location:{$_SERVER['HTTP_REFERER']}");
    }
}
$content = <<<EOT
<form>
    id:<input type="text" name="id" value="{$id}" /><button type="submit">查询</button>
</form>
EOT;
if ($id) {    
    $result = $searcher->query('id', $id)->search();
    if (!empty($result['docs'])) {
        $doc = current($result['docs']);
        $content .= "<form method=\"post\">";
        foreach ($doc as $field => $value) {
            $content .= <<<EOT
{$field}:<input type="text" size="60" name="{$field}" value="{$value}"/><br />   
EOT;
        }
        $content .= "<button type=\"submit\">更新</button></form>";
    } else {
        $content .= 'not found';
    }
}
echo $content; 
?>
