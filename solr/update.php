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
//$data = array(
//    'id' => 20000, 
//    'cid' => 20,
//    'actor' => 'Hugh Dennis Claire Skinner',
//    'director' => 'Andy Hamilton Guy Jenkin',
//    'content' => '教子有方第一季这是一部BBC出品，至今已推出五季的家庭爆笑喜剧！讲述一个平凡简单的英国五口之家，一对苦中作乐父母对抗（教养）三个可爱又伤脑筋的孩子所发生的趣事，也产生许多爆笑、温馨、欢乐的成长过程，随著季数的增加，这些孩子的实际年宁也在剧中跟著长大，能一路看这些孩子成长，也很生活很自然！更能体验到英国现实生活状况！',
//    'language' => '英语',
//    'year' => 2013,
//    'addtime' => 1358178735,
//    'hits' => 1,
//    'createdtime' => $searcher->formatDateTime(1358178735),
//    'title' => '教子有方第一季',
//);
//if (!$searcher->update($data)) {
//    print_r($searcher->lastError());
//}

?>
