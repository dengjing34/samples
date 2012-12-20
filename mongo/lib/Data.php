<?php
/**
 *
 * @author jingd <jingd3@jumei.com>
 */
abstract  class Data {
    protected static $connections = array();
    protected $fields = array();
    //avoid to new base class directly such as MongoData, MysqlData etc.
    private function __construct() {}
    
    protected function __clone() {
        foreach ($this->fields as $property => $field)
            $this->{$property} = null;        
    }

    abstract protected function load($id) ;
    
    abstract protected function loadByIds(array $ids);
    
    abstract protected function find(array $query) ;
    
    abstract protected function insert(array $data);
    
    abstract protected function update(array $data);
    
    abstract protected function save(array $data);
    
    abstract protected function remove($id);
    
    abstract protected function getConnection($flag = null);        
}

?>
