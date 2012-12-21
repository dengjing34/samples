<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";

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
//print_r($user->load(new MongoId('50bec1d7fe608c679cb4dbbe')));
//print_r($user->load(new MongoId('50bec14dfe608c679cb4dbbd')));
print_r($user->find(array(
    'name' => 'dengjing',
)));
$user1 = new User();
print_r($user1->find(array(), array('limit' => 1)));

$demo = new Demo();
//print_r($demo->load('50bd5f5cf3521a0f04000009'));
//print_r($demo->load('50bd5f5cf3521a0f04000005'));
print_r($demo->loadByIds(array('50bd5f5cf3521a0f04000009', '50bd5f5cf3521a0c04000000')));

class Test implements ArrayAccess, Iterator, Countable {
    function count() {
        return 0;
    }
    
    function offsetExists($offset) {
        return $offset;
    }
    
    function offsetGet($offset) {
        return $offset;
    }
    
    function offsetSet($offset, $value) {
        return $value . $offset;
    }
    
    function offsetUnset($offset) {
        return $offset;
    }
    
    function current() {
        return null;
    }
    
    function next() {
        return null;
    }
    
    function key() {
        return null;
    }
    
    function rewind() {
        return null;
    }
    
    function valid() {
        return null;
    }
}
?>
