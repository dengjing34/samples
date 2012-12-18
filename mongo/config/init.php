<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
date_default_timezone_set('PRC');
define('START_TIME', microtime(true));
define('BASEDIR', strtr(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR, '\\', '/'));
require_once BASEDIR . 'lib/AutoLoader.php';
AutoLoader::setLoadPath(BASEDIR . 'lib');
spl_autoload_register(array('AutoLoader', 'load'));
?>
