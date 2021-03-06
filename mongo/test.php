<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";
echo "<pre>";
try {
    $gourpUser = new User();
    $ret = $gourpUser->group(
            array('name'), 
            array('count' => 0, 'ageSum' => 0),
            'prev.count++;prev.ageSum += obj.age',
            array(array('age' => array('$gte' => 10)))
    );   
    print_r($ret);
    print_r($gourpUser->distinct('name'));
    $distinctUser = new User();
    $distinctUser->name = 'dengjing';
    var_dump($distinctUser->distinct('name'));
    $demo = new Demo();
    $ret = $demo->group(array('number'), array('count' => 0, 'sum' => 0), 'prev.count++;prev.sum += obj.number', array('number' => array('$lte' => 5)));
    print_r($ret);
} catch (Exception $e) {
    echo $e->getMessage();
}

$u = new User();
$u->find(array('name' => 'dengjing34'));
$uu = current($u->findResult());
$uu->name = 'dengjing34';
$uu->id = 43;
$uu->status = 0;
$uu->age = 25;
$uu->tag = array('movie', 'swimming', 'running', 'tennis');
try {
    $uu->save();
} catch (Exception $exc) {
    echo $exc->getMessage() . "\n";
}
print_r($uu);

$user = new User();
$user->shape = array(
    'height' => 171,
    'weight' => 64,
    'hair' => 'black',
);
$user->tag = array(
    0 => 'movie',
    1 => 'games',
    2 => 'program',
    3 => Array(
        'by' => 'shipping',
    ),
    4 => 'football',
);
$user->lang = array(
    '.net', 'c#', 'php', 'css', 'html'
);
print_r($user->findOne());
//print_r($user->load(new MongoId('50bec1d7fe608c679cb4dbbe')));
//print_r($user->load(new MongoId('50bec14dfe608c679cb4dbbd')));
$user3 = new User();
print_r($user3->findOne(
        array(
            'age' => array('$gt' => 20),
        ),
        array('name' => -1)
));
$user1 = new User();
print_r($user1->find()->sort(array('name' => -1))->findResult());


$user2 = new User();
print_r($user2->find(array('name' => 'dengjing'))->findResult());
var_dump($user2->count());
$demo = new Demo();
//print_r($demo->load('50bd5f5cf3521a0f04000009'));
//print_r($demo->load('50bd5f5cf3521a0f04000005'));
print_r($demo->loadByIds(array('50bd5f5cf3521a0f04000009', '50bd5f5cf3521a0c04000000')));
print_r($demo->find()->limit(3)->sort(array('name' => 1))->findResult());
print_r($demo->find()->limit(3)->sort(array('name' => -1))->findResult());
var_dump($demo->count(array('number' => 2)));
//$demo->number = 1;
//var_dump(MongoData::$db, MongoData::$collection, MongoData::$mongoCollection, MongoData::$mongoDb, MongoData::$mongoCursor);
?>
