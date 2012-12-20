<?php
/**
 * Description of MongoData
 *
 * @author jingd <jingd3@jumei.com>
 */
class MongoData extends Data{       
    const CONFIG_FILE = 'mongo';
    const DEFAULT_CONNECT_FLAG = '01';
    const DB_TEST = 'test';//by default select db
    public $_id;
    private $db, $collection, $className;
    private static $mongoCollection, $mongoDb;        

    final protected function init(array $options) {
        $requiredOptions = array('collection', 'fields');
        foreach (array_diff($requiredOptions, array_keys($options)) as $val) {
            throw new Exception("options['{$val}'] is required when init class");
        }
        if (array_values($options['fields']) == $options['fields']) $options['fields'] = array_combine($options['fields'], $options['fields']);        
        foreach (array('collection', 'fields') as $v) $this->{$v} = $options[$v];
        $this->className = get_class($this);
        $this->db = isset($options['db']) ? $options['db'] : self::DB_TEST;//if not specify $option['db'] use default
        foreach ($options['fields'] as $field) {
            if (!property_exists($this, $field)) $this->{$field} = null;
        }
    }
        
    final protected function getConnection($flag = self::DEFAULT_CONNECT_FLAG) {        
        try {
            $config = Config::item('mongo');            
            $mongo = new Mongo($config['host'], $config['options']);
            $mongo->setSlaveOkay();
        } catch (MongoConnectionException $e) {
            throw new Exception($e->getMessage());
        }
        self::$connections[__CLASS__][$flag] = $mongo;        
        return $mongo;
    }
    
    final private function connect($flag = self::DEFAULT_CONNECT_FLAG) {
        return isset(self::$connections[__CLASS__][$flag]) ? self::$connections[__CLASS__][$flag] : $this->getConnection($flag);
    }
    
    final private function getCollection() {
        if (isset(self::$mongoCollection[$this->collection])) {            
            return self::$mongoCollection[$this->collection];
        } else {
            self::$mongoCollection[$this->collection] = $this->connect()->selectCollection($this->db, $this->collection);
            return self::$mongoCollection[$this->collection];
        }
    }
    
    final private function getDb() {
        if (isset(self::$mongoDb[$this->db])) {
            return self::$mongoDb[$this->db];
        } else {
            self::$mongoDb[$this->db] = $this->connect()->selectDB($this->db);
            return self::$mongoDb[$this->db];
        }        
    }

    public function load($id = null) {
        $id = is_null($id) ? $this->_id : $id;
        if ($id instanceof MongoId) $id = (string)$id;
        if (!is_scalar($id) || !ctype_alnum((string)$id)) throw new Exception("{$id} is not a scalar");
        try {
            $result = $this->getCollection()->findOne(array('_id' => new MongoId($id)));
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }        
        if (empty($result)) throw new Exception("{$this->className}::{$id} not found");
        if (is_null($this->_id)) $this->_id = (string)$result['_id'];
        foreach ($this->fields as $property => $field) $this->{$property} = isset($result[$field]) ? $result[$field] : null;
        return $this;
    }
    
    public function find(array $query = array(), array $fields = array()) {
        $result = array();
        $finalQuery = $this->getQuery($query);//merge self properties and custom query
        print_r($finalQuery);
        try {
            foreach ($this->getCollection()->find($finalQuery, $fields) as $key => $value) {            
                $result[$key] = $this->parseDoc($value);
            }               
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }     
        return $result;
    }
    
    private function getQuery(array $query) {
        $result = array();
        if (!empty($this->_id)) $result['_id'] = $this->_id;
        foreach ($this->fields as $property => $field) {
            if (is_scalar($this->{$property}) && !empty($this->{$property})) {
                $result[$field] = $this->{$property}; //标量直接用等于
            } elseif (is_array($this->{$property}) && !empty($this->{$property})) {
                $result[$field] = $this->{$property};
            }
        }
        $result += $query;
        return $result;
    }    
    
    public function remove($id) {
        return $id;
    }
    
    public function save(array $data) {
        return $data;
    }
    
    public function loadByIds(array $ids) {
        $result = array();
        $ids = array_filter($ids, function($v){return is_scalar($v) && ctype_alnum((string)$v) ? true : false;});
        if (!empty($ids)) {
            $mongoIds = array_map(function($v) {
                        return new MongoId($v);
            }, $ids);
            $cursor = $this->getCollection()->find(array('_id' => array('$in' => $mongoIds)));
            //$data = iterator_to_array($cursor);                     
            foreach ($cursor as $key => $value) {
                $result[$key] = $this->parseDoc($value);
            }        
        }
        return $result;
    }
    
    public function insert(array $data) {
        return $data;
    }
    
    public function update(array $data) {
        return $data;
    }
    
    private function parseDoc(array $data) {        
        $o = clone $this;// use clone to keep Any properties that are references to other variables, such as MongoClient, MongoCollection, will remain references.
        $o->_id = isset($data['_id']) ? (string)$data['_id'] : null;
        foreach ($o->fields as $property => $field) {            
            $o->{$property} = isset($data[$field]) ? $data[$field] : null;
        }        
        return $o;
    }   
    
}
?>
