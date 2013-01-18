<?php
/**
 * 获取config目录下的配置文件的信息
 * @author dengjing34@vip.qq.com
 */
class Config {
    private static $conf = array();
    /**
     * 获取config目录下配置文件的信息 配置文件用return array();的方式来实现
     * @param string $key 配置文件的文件名以及数组的key, 如mysql.master.host表示查找config目录中mysql.php里面的['master']['host']的value
     * @return mixed 返回可能是string或者array(),根据配置文件而定
     */
    public static function item($key) {
        $keys = explode('.', $key);
        $file = current($keys);
        if (isset(self::$conf[$file])) $rs = self::$conf[$file];
        elseif (is_file(BASEDIR . "config/{$file}.php")) {
            $rs = self::$conf[$file] = include_once(BASEDIR . "config/{$file}.php");
        } else {
            throw new Exception("file [{$file}] not found in config dir");
        }
        array_shift($keys); //shift the file
        foreach ($keys as $k) {
            if (array_key_exists($k, (array)$rs)) $rs = $rs[$k];
            else throw new Exception("[{$k}] is undefined in file [{$key}]");
        }
        return $rs;
    }
}
?>
