<?php
/**
 *
 * @author jingd <jingd3@jumei.com>
 */
abstract  class Data {
    protected static $connections = array();
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
