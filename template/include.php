<?php
define('DIR', strtr(dirname(__FILE__) . DIRECTORY_SEPARATOR, '\\', '/'));
function render($filePath, $vars = array(), $print = false) {
    ob_start();
    extract($vars);
    include DIR . "{$filePath}.php";
    $res = ob_get_clean();
    if ($print) echo $res;
    else return $res;
}
?>
