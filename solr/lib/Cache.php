<?php
/**
 * Description of Cache
 *
 * @author jingd <jingd3@jumei.com>
 */
class Cache {
    const EXPIRE_30DAY = 2592000;
    private static $memcached = null, $instance = null;    
    /**
     * 单例模式 不允许new操作
     */
    private function __construct() {
        ;
    }

    /**
     * 获取Cache的实例
     * @return Cache
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取memcached的实例
     * @return \Memcached
     * @throws Exception 在config/memcache.php中找不到cache pool的配置时会抛异常
     */
    private function getMemcached() {
        if (is_null(self::$memcached)) {
            try {
                $servers = Config::item('memcache');
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
            $memcached = new Memcached();
            $memcached->addservers($servers);            
            self::$memcached = $memcached;
        }
        return self::$memcached;
    }
    
    /**
     * 往memcache里面存储一个元素
     * @param string $key 元素的key
     * @param mix $value 元素的value
     * @param int $expire 当前写入缓存的数据的失效时间。如果此值设置为0表明此数据永不过期。你可以设置以秒为单位的整数（从当前算起的时间差）来说明此数据的过期时间，2592000秒（30天）。
     * @return boolean 成功时返回 TRUE， 或者在失败时返回 FALSE.
     */
    public function set($key, $value, $expire = 0) {        
        return $this->getMemcached()->set($key, $value, $this->validateExpire($expire));
    }
    
    /**
     * 存储多个元素
     * @param array $items 存放在服务器上的键/值对数组
     * @param int $expire 到期时间，默认为 0
     * @return type 成功时返回 TRUE,或者在失败时返回 FALSE
     */
    public function setMulti(array $items, $expire = 0) {
        return $this->getMemcached()->setMulti($items, $this->validateExpire($expire));
    }
    
    /**
     * 与 Memcached::set()类似，但是如果 key已经在服务端存在，此操作会失败。
     * @param string $key 用于存储值的键名。
     * @param mix $value 存储的值
     * @param int $expire 到期时间，默认为 0。
     * @return boolean 成功时返回 TRUE,或者在失败时返回 FALSE 
     */
    public function add($key, $value, $expire = 0) {
        return $this->getMemcached()->add($key, $value, $this->validateExpire($expire));
    }
    
    /**
     * 从memcache服务端检回一个元素
     * @param string $key 元素的key
     * @return mix 返回key对应的存储元素的字符串值或者在失败或key未找到的时候返回FALSE。
     */
    public function get($key) {        
        return $this->getMemcached()->get($key);
    }
    
    /**
     * 检索多个元素
     * @param array $keys 要检索的key的数组
     * @return array|boolean 返回检索到的元素的数组,未检索到的key不会返回.或者在失败时返回 FALSE
     */
    public function getMulti(array $keys) {
        $null = null;
        return $this->getMemcached()->getMulti($keys, $null, Memcached::GET_PRESERVE_ORDER);
    }


    /**
     * 增加一个元素的值
     * 将指定元素的值增加value。如果指定的key 对应的元素不是数值类型并且不能被转换为数值， 会将此值修改为value.
     * 不会在key对应元素不存在时创建元素
     * @param string $key 要增加值的元素的key。
     * @param int $gap (默认为1)要将元素的值增加的大小。
     * @return boolean 成功时返回元素的新值 或者在失败时返回 FALSE。
     */
    public function increment($key, $offset = 1) {        
        return $this->getMemcached()->increment($key, (int)$offset);
    }
    
    /**
     * 减小一个数值元素的值,减小多少由参数offset决定。 
     * 如果元素的值不是数值，以0值对待。
     * 如果减小后的值小于0,则新的值被设置为0.如果元素不存在,Memcached::decrement() 失败。.
     * @param string $key 将要减小值的元素的key。
     * @param int $gap (默认为1)要将减小指定元素的值减小多少
     * @return type
     */
    public function decrement($key, $offset = 1) {
        return $this->increment()->decrement($key, (int)$offset);
    }
    
    /**
     * 从服务端删除key对应的元素. 参数time是一个秒为单位的时间(或一个UNIX时间戳表明直到那个时间), 
     * 用来表明 客户端希望服务端在这段时间拒绝对这个key的add和replace命令.
     * 由于这个时间段的存在, 元素被放入一个删除队列, 表明它不可以通过get命令获取到值,
     * 但是同时add和replace命令也会失败(无论如何set命令都会成功). 在这段时间过去后,
     * 元素最终被从服务端内存删除.time参数默认0(表明元素会被立即删除并且之后对这个 key的存储命令也会成功).
     * @param string $key 要删除元素的key
     * @param int $timeout (默认0)服务端等待删除该元素的总时间(或一个Unix时间戳表明的实际删除时间).
     * @return boolean 成功时返回 TRUE,或者在失败时返回 FALSE. 
     */
    public function delete($key, $time = 0) {
        return $this->getMemcached()->delete($key, (int)$time);
    }
    
    /**
     * 删除多个元素
     * @param array $keys 要删除的元素的key组成的数组
     * @param int $time (默认0)服务端等待删除该元素的总时间(或一个Unix时间戳表明的实际删除时间).
     * @return array 由删除元素keys为键 删除结果为值组成的数组.
     */
    public function deleteMulti(array $keys, $time = 0) {
        return $this->getMemcached()->deleteMulti($keys, (int)$time);
    }
    
    /**
     * 替换已存在key下的元素
     * Memcached::replace()和 Memcached::set()类似，但是如果 服务端不存在key， 操作将失败。
     * @param string $key 用于存储值的键名
     * @param mix $value 存储的值
     * @param int $expire 到期时间,默认为 0
     * @return boolean 成功时返回 TRUE,或者在失败时返回 FALSE.
     */
    public function replace($key, $value, $expire = 0) {
        return $this->getMemcached()->replace($key, $value, $this->validateExpire($expire));
    }
    
    /**
     * 获取服务器池中的服务器列表
     * @return array 服务器池中所有服务器列表.
     */
    public function getServerList() {
        return $this->getMemcached()->getServerList();
    }
    
    /**
     * 获取服务器池的统计信息
     * @return array 返回一个包含所有可用memcache服务器状态的数组
     */
    public function getstats() {
        return $this->getMemcached()->getstats();
    }
    
    /**
     * 作废缓存中的所有元素,立即（默认）或者在delay延迟后作废所有缓存中已经存在的元素。
     * @return boolean 成功时返回 TRUE,或者在失败时返回 FALSE
     */
    public function flush($time = 0) {
        return $this->getMemcached()->flush();
    }
    
    /**
     * 获取服务器池中所有服务器的版本信息
     * @return array 服务器版本信息的数组，每个服务器占一项。
     */
    public function getVersion() {
        return $this->getMemcached()->getVersion();
    }
    
    /**
     * 验证过期时间是否大于30天
     * @param int $expire 需要验证的过期时间
     * @return int 过期时间,单位:秒
     */
    private function validateExpire($expire) {
        return (int)$expire > self::EXPIRE_30DAY ? self::EXPIRE_30DAY : (int)$expire;
    }
}
?>
