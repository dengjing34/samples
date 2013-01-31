<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
header('Content-type:text/html; charset=utf-8');    
require_once strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
$q = isset($_GET['q']) ? $_GET['q'] : '';
$videoSearcher = new VideoSearcher();
$result = $videoSearcher->hl()
        ->hlFieldList(array('content', 'title', 'actor', 'actor'))
        ->hlFragsize(200)
        ->hlSnippets(2)
        ->defaultQuery($q)
        ->search();
$content = <<<EOT
<style>
em{color:red;}
</style>
<form>
    <input type="q" name="q" value="{$q}" /><button type="submit">搜索</button>
</form>
<pre>
EOT;
echo $content;
foreach ($result['highlighting'] as $id => $v) {
    echo "{$id} => " . implode("\n", $v['content']) . "\n";
}
print_r($result);
?>
