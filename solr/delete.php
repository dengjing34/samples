<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');    
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$id = isset($_POST['id']) ? $_POST['id'] : null;
$ids = isset($_POST['ids']) ? $_POST['ids'] : null;
$query = isset($_POST['query']) ? $_POST['query'] : null;
if ($_POST) {
    $searcher = new VideoSearcher();
    $deleted = array();
    if ($id) {
        if (!$searcher->delete($id)) {
            echo '<pre>';
            print_r($searcher->lastError());
            die;
        }
        $deleted[] += $id;
    }
    if ($ids) {
        $idArr = explode(',', $ids);
        if (!$searcher->deleteByIds($idArr)) {
            echo '<pre>';
            print_r($searcher->lastError());
            die;            
        }
        $deleted += $idArr;
    }
    if ($query && !$searcher->deleteByQuery($query)) {
        echo '<pre>';
        print_r($searcher->lastError());
        die;
    }
    echo implode(',', $deleted) . "had been deleted <a href=\"{$_SERVER['HTTP_REFERER']}\">back</a>";
    die;
} 
$html = <<<EOT
<form method="post">
    id:<input type="text" name="id" /><br />
    ids:<input type="text" name="ids" /><br />
    query:<input type="text" name="query" /><br />
    <button type="submit">删除</button>
</form>
EOT;
echo $html;
?>
