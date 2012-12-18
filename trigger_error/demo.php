<?php
$name = isset($_GET['name']) ? $_GET['name']: null;
$errorType = array(
    E_USER_ERROR,
    E_USER_NOTICE,
    E_USER_WARNING,
);
if (!$name) {
    trigger_error("need a name", $errorType[array_rand($errorType)]);
}
var_dump($name);
?>
