<?php
$o = new Redis();
$o->connect('127.0.0.1');

var_dump($o->set('name', 'dengjing'));
var_dump($o->get('name'));
?>
