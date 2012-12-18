<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
require strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/') . "config/init.php";

$user = new User();
//print_r($user->load(new MongoId('50bec1d7fe608c679cb4dbbe')));
//print_r($user->load(new MongoId('50bec14dfe608c679cb4dbbd')));
print_r($user->find());

$demo = new Demo();
//print_r($demo->load('50bd5f5cf3521a0f04000009'));
//print_r($demo->load('50bd5f5cf3521a0f04000005'));
print_r($demo->loadByIds(array('50bd5f5cf3521a0f04000009', '50bd5f5cf3521a0c04000000')));
?>
