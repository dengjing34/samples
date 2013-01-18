<?php
/**
 * Description of AutoLoader
 *
 * @author jingd <jingd3@jumei.com>
 */
class AutoLoader {
    public static $path;
    public static function load($className) {
        if (is_null(self::$path)) throw new Exception('autoload path is undefiend');
        $file = self::$path . "/{$className}.php";        
        if (is_file($file))
            require_once $file;
        else 
            throw new Exception("{$file} not exists");
    }
    
    public static function setLoadPath($path) {
        self::$path = $path;
    }
}

?>
