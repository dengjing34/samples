<?php
/**
 * Description of Cache
 *
 * @author jingd <jingd3@jumei.com>
 */
class Cache {
    private static $memcache = null, $o = null;
    
    private function instance() {
        if (is_null(self::$memcache)) {
            try {
                $servers = Config::item('memcache');
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
            $memcache = new Memcache();
            foreach ($servers as $server) {
                $memcache->addserver($server['host'], $server['port']);
            }
            self::$memcache = $memcache;
        }
        return self::$memcache;
    }
    
    public function set($key, $value, $ttl = 0) {
        return $this->instance()->set($key, $value, 0, (int)$ttl <= 2592000 ? (int)$ttl : 2592000);
    }
    
    public function get($key) {        
        return $this->instance()->get($key);
    }
    
    public function increment($key, $gap = 1) {        
        return $this->instance()->increment($key, $gap);
    }
    
    public function decrement($key, $gap = 1) {
        return $this->increment()->decrement($key, $gap);
    }
    
    public function delete($key, $timeout = 0) {
        return $this->instance()->delete($key, $timeout);
    }
    
    public function replace($key, $value, $ttl = 0) {
        return $this->instance()->replace($key, $value, 0, (int)$ttl <= 2592000 ? (int)$ttl : 2592000);
    }
}
?>
