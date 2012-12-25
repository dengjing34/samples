<?php
/**
 *
 * @author jingd <jingd3@jumei.com>
 */
abstract class Data {
    protected static $connections = array();
    protected static $fields = array();
    //avoid to new base class directly such as MongoData, MysqlData etc.
    private function __construct() {}
    
    protected function __clone() {
        $this->clean();       
    }
    
    /**
     * clean object's properties by $fields
     * @return $this
     */
    protected function clean() {        
        foreach (self::$fields[get_parent_class($this)][get_class($this)] as $property => $field)
            $this->{$property} = null;
        return $this;
    }

    abstract protected function load($id) ;
    
    abstract protected function loadByIds(array $ids);
    
    abstract protected function find(array $query) ;
    
    abstract protected function insert(array $data);
    
    abstract protected function update(array $data);
    
    abstract protected function save(array $data);
    
    abstract protected function remove($id = null);
    
    abstract protected function count(array $query);
    
    abstract protected function getConnection($flag = null);        
}

?>
