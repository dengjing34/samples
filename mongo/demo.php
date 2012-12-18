<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
try {
    $mongo = new Mongo('mongodb://localhost:27017');    
} catch (MongoConnectionException $e) {
    die($e->getMessage());
}
$db = $mongo->test;//select db
$collection = $db->demo;// select collection
function urlGet($arg) {
    return isset($_GET[$arg]) ? $_GET[$arg] : null;
}
function pager($total) {
    $key = 'page';
    $pageSize = 10;    
    $currPage = isset($_GET[$key]) ? abs((int)($_GET[$key])) : 1;
    $currPage = $currPage < 1 ? 1 : $currPage;   
    if (isset($_GET[$key])) unset ($_GET[$key]);
    $params = array_filter($_GET);
    $pageNum = ceil($total / $pageSize);
    $pageNum = $pageNum == 0 ? 1 : $pageNum;
    $currPage = $currPage > $pageNum ? $pageNum : $currPage;
    $ret = array();
    if ($currPage == 1) {       
        $ret['prev'] = "<span>prev</span>";
    } else {
        $queryString = http_build_query(array_merge($params, array($key => $currPage - 1)));
        $ret['prev'] = "<a href=\"?{$queryString}\">prev</a>";
    }
    if ($currPage == $pageNum) {
        $ret['next'] = "<span>next</span>";
    } else {
        $queryString = http_build_query(array_merge($params, array($key => $currPage + 1)));
        $ret['next'] = "<a href=\"?{$queryString}\">next</a>";
    }
    return "total:{$total} " . join(' ', $ret) . " current {$currPage}/{$pageNum}";
}
$count = $collection->count();
$filds = array('name', 'number', 'age');
//delete
if (urlGet('a') == 'del' && (bool)($_id = urlGet('_id'))) {
    try {
        $collection->remove(array('_id' => new MongoId($_id)));
        die("{$_id} has been deleted <a href=\"{$_SERVER['HTTP_REFERER']}\">back</a>");
    } catch (Exception $e) {
        die($e->getMessage());
    }    
}
if ($count < 100) {
    $letters = array_flip(range('a', 'z'));    
    for ($i = 1; $i <=10; $i++) {
        //insert
        $name = implode(array_rand($letters, 5));
        $collection->save(array(
            'name' => $name,
            'number' => $i,
            'age' => 20 + $i,
            'createdTime' => time(),
        ));
    }
}
$filter = array();
foreach ($filds as $val) {
    if((bool)($v = urlGet($val))) {
        switch ($val) {
            case 'name':
                $filter[$val] = new MongoRegex("/{$v}/");
                break;
            default:
                $filter[$val] = is_numeric ($v) ? (int)$v : $v;
                break;
        }        
    }    
}
$pageSize = 10;
$page = (int)(urlGet('page'));
$page = $page < 1 ? 1 : $page;

$cursor = $collection->find($filter)->sort(array('_id' => 1))->skip(($page - 1) * $pageSize)->limit($pageSize);
$total = $cursor->count();
//print_r(iterator_to_array($data));
?>
<form>
    <?php
    foreach ($filds as $val) {
    ?>
    <?=$val?>:<input type="text" name="<?=$val?>" value="<?=  urlGet($val)?>" /><br />
    <?php
    }
    ?>
    <button type="submit">search</button>
</form>
<?php
//modify
if (urlGet('a') == 'edit' && (bool)($_id = urlGet('_id'))) {       
    if (!is_null($one = $collection->findOne(array('_id' => new MongoId($_id))))) {               
        if (count(array_intersect_key(array_flip($filds), array_filter($_POST))) == count($filds)) {                        
            foreach ($filds as $val) $one[$val] = $_POST[$val];
            try {
                $collection->save($one);
                header("Location: {$_SERVER['HTTP_REFERER']}");
            } catch (Exception $e) {
                die($e->getMessage());
            }            
        }
        $editHtml = array('<hr /><h3>edit</h3><form method="post">');        
        foreach ($filds as $val) {
            $editHtml[] = "{$val}:<input type=\"text\" name=\"{$val}\" value=\"{$one[$val]}\"><br />";
        } 
        $editHtml[] = '<button type="submit">submit</button></form>';
        echo join("\n", $editHtml);
    }    
}
?>
<?php if (!empty($filter)) echo '<p>filter:</p><pre>' . var_export($filter, true) . '</pre>';?>
<p><?=  pager($total)?></p>
<table border="1">
    <tr>
        <td>no</td>
        <td>option</td>
        <td>id</td>
        <td>name</td>
        <td>number</td>
        <td>age</td>
        <td>createdTime</td>
    </tr>
    <?php
    $html = array();
    $i = 1;
    $queryString = array_filter($_GET);   
    foreach ($cursor as $o) {       
        $editUrl = http_build_query(array_merge($queryString, array('a' => 'edit', '_id' => (string)$o['_id'])));
        $delUrl = http_build_query(array_merge($queryString, array('a' => 'del', '_id' => (string)$o['_id'])));
        $dateTime = date('Y-m-d H:i:s', $o['createdTime']);
        $html[] = "<tr><td>{$i}</td><td><a href=\"?{$editUrl}\">edit</a> <a onclick=\"return confirm('confirm delete?') ? true : false;\" href=\"?{$delUrl}\">del</a></td><td>{$o['_id']}</td><td>{$o['name']}</td><td>{$o['number']}</td><td>{$o['age']}</td><td>{$dateTime}</td></tr>";
        $i++;
    }
    echo join("\n", $html);
    ?>
</table>