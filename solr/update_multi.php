<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');    
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$searcher = new VideoSearcher();
if (!empty($_POST)) {
    $data = array();
    $keys = array_keys(array_diff_key($_POST, array('id' => 0)));        
    foreach ($_POST['id'] as $key => $id) {
        $data[$id]['id'] = $id;
        foreach ($keys as $field) {
            $data[$id][$field] = isset($_POST[$field][$key]) ? $_POST[$field][$key] : '';
        }
    }
    if (!$searcher->update($data)) {
        echo '<pre>';
        print_r($searcher->lastError());
        die;
    } 
    header("Location: {$_SERVER['HTTP_REFERER']}");
}
$ids = isset($_GET['ids']) ? $_GET['ids'] : null;
$result = array();
if (!is_null($ids)) {
    $idsArr = explode(',', $ids);    
    $result = $searcher->inQuery('id', $idsArr)->search();    
}
$html = <<<EOT
<form>
    <input type="text" name="ids" size="60" value="{$ids}" /><button type="submit">搜索id</button>example:123,124,125
</form>
EOT;
    
if (isset($result['docs'])) {
    $html .= '<form method="post">';
    foreach ($result['docs'] as $doc) {        
        foreach ($doc as $field => $value) {
        $html .= <<<EOT
{$field}:<input type="text" name="{$field}[]" value="{$value}" />
EOT;
        }
        $html .= '<br /><br />';
    }
    $html .= '<br /><button type="submit">更新</button></form>';
}    
echo $html;
?>
