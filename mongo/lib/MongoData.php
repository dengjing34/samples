<?php
/**
 * ODM of MongoData
 *
 * @author jingd <jingd3@jumei.com>
 */
class MongoData extends Data implements Iterator{       
    const CONFIG_FILE = 'mongo';
    const DEFAULT_CONNECT_FLAG = '01';
    const DB_TEST = 'test';//by default select db
    public $_id;
    private $db, $collection, $mongoCursor;
    private static $mongoCollection, $mongoDb, $counter = 0;        
   
    /**
     * when a class extends MongoData  __construct, it must config fields and collection with this method
     * @param array $options an array must with keys 'collection', 'fields'
     * @throws Exception valid failed message
     */
    final protected function init(array $options) {
        $requiredOptions = array('collection', 'fields');
        foreach (array_diff($requiredOptions, array_keys($options)) as $val) {
            throw new Exception("options['{$val}'] is required when init class");
        }
        if (array_values($options['fields']) == $options['fields']) $options['fields'] = array_combine($options['fields'], $options['fields']);        
        $this->collection = $options['collection'];
        $this->fields = $options['fields'];
        $this->db = isset($options['db']) ? $options['db'] : self::DB_TEST;//if not specify $option['db'] use default
        foreach ($options['fields'] as $field) {
            if (!property_exists($this, $field)) $this->{$field} = null;
        }
    }
    
    /**
     * get \Mongo instance
     * @param type $flag    key to store this \Mongo instance at self::$connection[__CLASS__][$flag]
     * @return \Mongo
     * @throws Exception message of MongoConnectionException
     */
    final protected function getConnection($flag = self::DEFAULT_CONNECT_FLAG) {        
        try {
            $config = Config::item('mongo');            
            $mongo = new Mongo($config['host'], $config['options']);
            $mongo->setSlaveOkay();
        } catch (MongoConnectionException $e) {
            throw new Exception($e->getMessage());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        self::$connections[__CLASS__][$flag] = $mongo;        
        return $mongo;
    }
    
    final private function connect($flag = self::DEFAULT_CONNECT_FLAG) {
        return isset(self::$connections[__CLASS__][$flag]) ? self::$connections[__CLASS__][$flag] : $this->getConnection($flag);
    }
    
    /**
     * get \MongoCollection instance
     * @return \MongoCollection 
     */
    final private function getCollection() {
        if (isset(self::$mongoCollection[$this->collection])) {            
            return self::$mongoCollection[$this->collection];
        } else {
            self::$mongoCollection[$this->collection] = $this->connect()->selectCollection($this->db, $this->collection);
            return self::$mongoCollection[$this->collection];
        }
    }
    
    /**
     * get \MongoDB instance
     * @return \MongoDB 
     */
    final private function getDb() {
        if (isset(self::$mongoDb[$this->db])) {
            return self::$mongoDb[$this->db];
        } else {
            self::$mongoDb[$this->db] = $this->connect()->selectDB($this->db);
            return self::$mongoDb[$this->db];
        }        
    }

    /**
     * 
     * @param string|MongoId $id field '_id' value
     * @return \MongoData $this
     * @throws Exception when load a document faild throw a exception 
     */
    public function load($id = null) {
        $id = is_null($id) ? $this->_id : $id;
        if ($id instanceof MongoId) $id = (string)$id;
        if (!is_scalar($id) || !ctype_alnum((string)$id)) throw new Exception("{$id} is not a scalar");
        try {
            $result = $this->getCollection()->findOne(array('_id' => new MongoId($id)));
            $this->increaseCounter();
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }        
        if (empty($result)) throw new Exception(get_class($this) . "::{$id} not found");
        if (is_null($this->_id)) $this->_id = (string)$result['_id'];// can't use $this->parseDoc($result) 
        foreach ($this->fields as $property => $field) $this->{$property} = isset($result[$field]) ? $result[$field] : null;
        return $this;
    }
    
    /**
     * 
     * @param array $query Associative array with fields to match.
     * @param int $limit Specifies an upper limit to the number returned.
     * @param int $skip Specifies a number of results to skip before starting the count.
     * @return int Returns the number of documents matching the query.
     */
    public function count(array $query = array(), $limit = 0, $skip = 0) {
        $this->increaseCounter();
        return $this->getCollection()->count($this->getQuery($query), (int)$limit, (int)$skip);
    }
    
    /**
     * 
     * @param array $query The fields for which to search
     * @return \MongoData MongoData object
     * @throws Exception  with MongoCursorException message
     */
    public function find(array $query = array()) {        
        $finalQuery = $this->getQuery($query);//merge self properties and custom query                
        try {
            $this->mongoCursor = $this->getCollection()->find($finalQuery);                        
            $this->increaseCounter();
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }     
        return $this;        
    }
    
    /**
     * find only one doc by query
     * @param array $query query criteria
     * @param array $sort sort criteria
     * @return object the one doc that find in mongodb, return null if not exists
    */
    public function findOne(array $query = array(), array $sort = array()) {
        $result = iterator_to_array($this->find($query)->sort($sort)->limit(1));
        $this->increaseCounter();
        return current($result) ? current($result) : null;
    }
    
    /**
     * 
     * @param array $sort An array of fields by which to sort. Each element in the array has as key the field name, and as value either 1 for ascending sort, or -1 for descending sort.
     * @return \MongoData MongoData object
     */
    public function sort(array $sort = array()) {
        if (!empty($sort) && $this->mongoCursor instanceof MongoCursor)
            $this->mongoCursor->sort($sort);       
        return $this;
    }
    
    /**
     * 
     * @param int $num The number of results to skip.
     * @return \MongoData MongoData object
     */
    public function skip($num) {
        if (is_numeric($num) && $this->mongoCursor instanceof MongoCursor)
            $this->mongoCursor->skip((int)$num);
        return $this;
    }
    
    /**
     * 
     * @param int $num The number of results to return.
     * @return \MongoData MongoData object
     */
    public function limit($num) {
        if (is_numeric($num) && $this->mongoCursor instanceof MongoCursor)
            $this->mongoCursor->limit((int)$num);
        return $this;
    }
    
    /**
     * increase mongodb query times
     * @return \MongoData $this
     */
    final private function increaseCounter() {
        self::$counter++;
        return $this;
    }
    
    /**
     * 
     * @return int query mongodb times
     */
    public static function getCounter() {
        return self::$counter;
    }

    /**
     * 
     * @param array $query build query criteria by object properties and query params
     * @return array the final query criteria
     */
    private function getQuery(array $query) {
        $result = array();
        $forbiddenOperators = array('sort', 'skip', 'limit');        
        if (!empty($this->_id)) $result['_id'] = $this->_id;
        foreach ($this->fields as $property => $field) {
            if (is_scalar($this->{$property}) && !empty($this->{$property})) {
                $result[$field] = $this->{$property}; //if is scalar use equal directly
            } elseif (is_array($this->{$property}) && !empty($this->{$property})) {
                $result[$field] = $this->{$property};
            }
        }        
        return array_diff_key($result + $query, array_flip($forbiddenOperators));//avoid operators like 'sort', 'skip', 'limit' to be queried
    }    
    
    public function remove($id) {
        return $id;
    }
    
    /**
     * 
     * @param array $options
     * <p>
     * $options can be blow values<br />
     * "w" => The default value for MongoClient is 1. <br /><br />
     * "fsync" => Boolean, defaults to FALSE. Forces the insert to be synced to disk before returning success. If TRUE, an acknowledged insert is implied and will override setting w to 0. <br /><br />
     * "timeout" => Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response.
     * If the database does not respond within the timeout period, a MongoCursorTimeoutException will be thrown.<br /><br />
     * "safe" => Deprecated. Please use the WriteConcern w option.     
     * </p> 
     * @return \MongoData
     * @throws Exception
     * @link http://php.net/manual/zh/mongocollection.save.php
     */
    public function save(array $options = array()) {
        $allowOptions = empty($options) ? $options : array_intersect_key($options, array_flip(array('w', 'fsync', 'timeout', 'safe')));
        try {
            $this->getCollection()->save($this->parsePropery(true), $options);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }        
        return $this;
    }
    
    /**
     * 
     * @param array $ids ids to be query in 
     * @return array an array include matched documents as MongoData object
     */
    public function loadByIds(array $ids) {
        $result = array();
        $ids = array_filter($ids, function($v){return is_scalar($v) && ctype_alnum((string)$v) ? true : false;});
        if (!empty($ids)) {
            $mongoIds = array_map(function($v) {
                return new MongoId($v);
            }, $ids);
            $cursor = $this->getCollection()->find(array('_id' => array('$in' => $mongoIds)));//don't use $this->find() here, only '_id' will be used to query
            //$data = iterator_to_array($cursor);
            foreach ($cursor as $key => $value) {
                $result[$key] = $this->parseDoc($value);
            }
        }
        return $result;
    }
    
    
    /**
     * inset data to mongodb
     * @param array $options
     * <p>
     * $options can be blow values<br />
     * "w" => The default value for MongoClient is 1. <br /><br />
     * "fsync" => Boolean, defaults to FALSE. Forces the insert to be synced to disk before returning success. If TRUE, an acknowledged insert is implied and will override setting w to 0. <br /><br />
     * "timeout" => Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response.
     * If the database does not respond within the timeout period, a MongoCursorTimeoutException will be thrown.<br /><br />
     * "safe" => Deprecated. Please use the WriteConcern w option.     
     * </p> 
     * @return \MongoData
     * @throws Exception
     * @link http://php.net/manual/zh/mongocollection.insert.php
     */
    public function insert(array $options = array()) {
        $allowOptions = !empty($options) ? array_intersect_key($options, array_flip(array('w', 'fsync', 'timeout', 'safe'))) : $options;
        try {
            $this->getCollection()->insert($this->parsePropery(), $allowOptions);   
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }        
        return $this;
    }
    
    /**
     * update mongodb by query "_id" only
     * @param array $options
     * <p>
     * $options can be blow values<br />
     * "w" => The default value for MongoClient is 1. <br /><br />
     * "upsert" => If no document matches $criteria, a new document will be inserted.
     * "multiple" => All documents matching $criteria will be updated.it updates one document by default, not all matching documents. 
     * It is recommended that you always specify whether you want to update multiple documents or a single document, as the database may change its default behavior at some point in the future.<br /><br />
     * "fsync" => Boolean, defaults to FALSE. Forces the insert to be synced to disk before returning success. If TRUE, an acknowledged insert is implied and will override setting w to 0.<br /><br />
     * "timeout" => Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response.
     * If the database does not respond within the timeout period, a MongoCursorTimeoutException will be thrown.<br /><br />
     * "safe" => Deprecated. Please use the WriteConcern w option.     
     * </p> 
     * @return \MongoData $this
     * @link http://php.net/manual/zh/mongocollection.update.php
     */
    public function update(array $options = array()) {
        if (ctype_alnum((string)$this->_id) && is_scalar($this->_id)) {            
            $criteria = array('_id' => new MongoId($this->_id));
            $allowOptions = empty($options) ? $options : array_intersect_key($options, array_flip(array('w', 'upsert', 'multiple', 'fsync', 'timeout', 'safe')));
            try {
                $this->getCollection()->update($criteria, $this->parsePropery(), $allowOptions);
            } catch (Exception $e) {
                throw new $e->getMessage();
            }            
        } else {
            throw new Exception("_id:{$this->_id} is not alphanumeric or scalar");
        }
        return $this;
    }
    
    /**
     * 
     * @param array $data the document to be assign to the cloned object
     * @return \MongoData a cloned MongoData object
     */
    private function parseDoc(array $data) {        
        $o = clone $this;// use clone to keep Any properties that are references to other variables, such as MongoClient, MongoCollection, will remain references.
        $o->reset($o);
        $o->_id = isset($data['_id']) ? (string)$data['_id'] : null;
        foreach ($o->fields as $property => $field) {            
            $o->{$property} = isset($data[$field]) ? $data[$field] : null;
        }        
        return $o;
    }
    
    /**
     * pase object mapping perporties to an array
     * @param bool $_id if include _id field , 'true' means include 'false' means not
     * @return array an array with mongodb fields as keys
     */
    private function parsePropery($_id = false) {
        $data = $_id ? array('_id' => new MongoId($this->_id)) : array();
        foreach ($this->fields as $property => $field) $data[$field] = $this->{$property};
        return $data;
    }
    
    /**
     * clean this object 
     * @param \MongoData $o MongoData object to be clean
     * @return \MongoData clean MongoData object
     */
    private function reset($o) {
        foreach (array('mongoCursor') as $v) {
            if (property_exists($o, $v)) $o->{$v} = null;
        }
        return $o;
    }

    public function current() {
        $result = $this->mongoCursor instanceof MongoCursor ? $this->parseDoc($this->mongoCursor->current()) : null;        
        return $result;
    }

    public function next() {
        return $this->mongoCursor instanceof MongoCursor ? $this->mongoCursor->next() : null;
    }

    public function key() {
        return $this->mongoCursor instanceof MongoCursor ? $this->mongoCursor->key() : null;
    }

    public function valid() {
        return $this->mongoCursor instanceof MongoCursor ? $this->mongoCursor->valid() : false;
    }

    public function rewind() {
        return $this->mongoCursor instanceof MongoCursor ? $this->mongoCursor->rewind() : null;
    }
     
}
?>
