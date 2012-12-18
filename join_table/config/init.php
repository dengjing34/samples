<?php
/**
 * @author jingd <jingd3@jumei.com>
 */
date_default_timezone_set('PRC');
define('START_TIME', microtime(true));
define('BASEDIR', strtr(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR, '\\', '/'));
spl_autoload_register(array('AutoLoader', 'load'));
class AutoLoader {
    public static $dirname = 'lib';
    public static function load($className) {
        $file = BASEDIR . self::$dirname .  "/{$className}.php";        
        if (is_file($file))
            require_once $file;
        else 
            throw new Exception("{$file} not exists");
    }
}
?>
