<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$result = false;
$params = array(
    'q',
    'title',
    'year',
    'createdtime_s',
    'createdtime_e',
    'sortField',
    'sortValue',
);
foreach ($params as $v) ${$v} = isset($_GET[$v]) ? $_GET[$v] : null;
$page = isset($_GET['page']) && ctype_digit((string)$_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] : 1;
$sort = $sortField && $sortValue ? array($sortField => $sortValue) : array('id' => 'desc');
if (!empty($_GET)) {
    Pager::$pageSize = 20;
    $searcher = new VideoSearcher();
    $result = $searcher->setRows(Pager::$pageSize)                
            ->dateRangeQuery('createdtime', $createdtime_s, $createdtime_e)
            ->defaultQuery($q)
            ->query('title', $title)
            ->query('year', $year)
            ->setPage($page)
            ->setFieldList(array('title', 'createdtime', 'addtime', 'year', 'actor', 'language', 'content', 'hits'))
            ->sort($sort)
            ->search();            
}
?>
<form method="get">
    <table>
        <tr>
            <td>关键字:</td><td><input type="text" name="q" value="<?=$q?>" /></td>
        </tr>
        <tr>
            <td>影片名称:</td><td><input type="text" name="title" value="<?=$title?>" /></td>
        </tr>
        <tr>            
            <td>影片年份:</td><td><input type="text" name="year" value="<?=$year?>"/></td>            
        </tr>
        <tr>
            <td>上线时间:</td><td><input type="text" name="createdtime_s" value="<?=$createdtime_s?>"/> - <input type="text" name="createdtime_e" value="<?=$createdtime_e?>"/></td>
        </tr>
        <tr>            
            <td>排序:</td>
            <td>类型:
                <select name="sortField">
                    <option value=""></option>
                    <option value="createdtime" <?=$sortField == 'createdtime' ? 'selected' : null?>>录入时间</option>
                    <option value="hits" <?=$sortField == 'hits' ? 'selected' : null?>>点击数</option>
                    <option value="year" <?=$sortField == 'year' ? 'selected' : null?>>影片年份</option>
                </select>
                顺序:
                <select name="sortValue">
                    <option value=""></option>
                    <option value="asc" <?=$sortValue == 'asc' ? 'selected' : null?>>正序</option>
                    <option value="desc" <?=$sortValue == 'desc' ? 'selected' : null?>>倒序</option>                    
                </select>
            </td>            
        </tr>
        <tr>
            <td>&nbsp;</td><td><button type="submit">搜索</button></td>
        </tr>
    </table>
</form>
<?php
if ($result) {
    $pager = Pager::showPage($result['numFound']);
    echo $pager;
    $docsHtml[] = '<table border=1>';
    $docsHtml[] = '<tr style="color:blue;"><td>id</td><td>影片名称</td><td>点击数</td><td>影片年份</td><td>演员</td><td>语言</td><td>录入时间</td><td>时间戳</td></tr>';
    foreach ($result['docs'] as $doc) {
        $colspan = count($doc) - 1;
        $docsHtml[] = "<tr><td>{$doc['id']}</td><td>{$doc['title']}</td><td>{$doc['hits']}</td><td>{$doc['year']}</td><td>{$doc['actor']}</td><td>{$doc['language']}</td><td>{$doc['createdtime']}</td><td>{$doc['addtime']}</td></tr><tr><td colspan=\"{$colspan}\">{$doc['content']}</td></tr>";
    }
    $docsHtml[] = '</table>';
    echo join("\n", $docsHtml);
    echo $pager;
}
?>