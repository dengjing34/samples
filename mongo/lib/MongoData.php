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
    private static $db = array(), $collection = array(), $mongoCollection = array(), $mongoDb = array(), $mongoCursor = array(), $counter = 0;        
   
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
        $className = get_class($this);
        self::$collection[__CLASS__][$className] = $options['collection'];
        self::$fields[__CLASS__][$className] = $options['fields'];
        self::$fieldsAttributes[__CLASS__][$className] = isset($options['fieldsAttributes']) ? $options['fieldsAttributes'] : array();
        self::$db[__CLASS__][$className] = isset($options['db']) ? $options['db'] : self::DB_TEST;//if not specify $option['db'] use default
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
        $dbKey = self::$db[__CLASS__][get_class($this)];
        $collectionKey = self::$collection[__CLASS__][get_class($this)];
        if (isset(self::$mongoCollection[$collectionKey])) {            
            return self::$mongoCollection[$collectionKey];
        } else {
            self::$mongoCollection[$collectionKey] = $this->connect()->selectCollection($dbKey, $collectionKey);
            return self::$mongoCollection[$collectionKey];
        }
    }
    
    /**
     * get \MongoDB instance
     * @return \MongoDB 
     */
    final private function getDb() {
        $key = self::$db[__CLASS__][get_class($this)];
        if (isset(self::$mongoDb[$key])) {
            return self::$mongoDb[$key];
        } else {
            self::$mongoDb[$key] = $this->connect()->selectDB($key);
            return self::$mongoDb[$key];
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
        $fields = array_fill_keys($this->getFields(), true);
        try {
            $result = $this->getCollection()->findOne(array('_id' => new MongoId($id)), $fields);
            $this->increaseCounter();
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }        
        if (empty($result)) throw new Exception(get_class($this) . "::{$id} not found");
        if (is_null($this->_id)) $this->_id = (string)$result['_id'];// can't use $this->parseDoc($result) 
        foreach ($this->getFields() as $property => $field) $this->{$property} = isset($result[$field]) ? $result[$field] : null;
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
            $fields = array_fill_keys($this->getFields(), true);            
            $this->setMongoCursor($this->getCollection()->find($finalQuery, $fields));                        
            $this->increaseCounter();
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }     
        return $this;        
    }
    
    public function findResult() {
        $result = array();
        if ($this->getMongoCursor() instanceof MongoCursor)
            $result = iterator_to_array ($this);
        return $result;
    }
    
    /**
     * find only one doc by query
     * @param array $query query criteria
     * @param array $sort sort criteria
     * @return object the one doc that find in mongodb, return null if not exists
    */
    public function findOne(array $query = array(), array $sort = array()) {
        $result = $this->find($query)->sort($sort)->limit(1)->findResult();
        $this->increaseCounter();
        return current($result) ? current($result) : null;
    }
    
    /**
     * 
     * @param array $sort An array of fields by which to sort. Each element in the array has as key the field name, and as value either 1 for ascending sort, or -1 for descending sort.
     * @return \MongoData MongoData object
     */
    public function sort(array $sort = array()) {
        if (!empty($sort) && $this->getMongoCursor() instanceof MongoCursor)
            $this->getMongoCursor()->sort($sort);       
        return $this;
    }
    
    /**
     * 
     * @param int $num The number of results to skip.
     * @return \MongoData MongoData object
     */
    public function skip($num) {
        if (is_numeric($num) && $this->getMongoCursor() instanceof MongoCursor)
            $this->getMongoCursor()->skip((int)$num);
        return $this;
    }
    
    /**
     * 
     * @param int $num The number of results to return.
     * @return \MongoData MongoData object
     */
    public function limit($num) {
        if (is_numeric($num) && $this->getMongoCursor() instanceof MongoCursor)
            $this->getMongoCursor()->limit((int)$num);
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
     * The distinct command returns a list of distinct values for the given key across a collection.
     * @param string $key The key to use
     * @param array $query An optional query parameters
     * @return array an array of distinct values, or FALSE on failure
     */
    public function distinct($key, array $query = array()) {
        return !empty($key) && array_key_exists($key, $this->getFields()) ? $this->getCollection()->distinct($key, $this->getQuery($query)) : array();
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
        foreach ($this->getFields() as $property => $field) {
            if (is_scalar($this->{$property}) && !empty($this->{$property})) {
                $result[$field] = $this->{$property}; //if is scalar use equal directly
            } elseif (is_array($this->{$property}) && !empty($this->{$property})) {
                $result[$field] = $this->{$property};
            }
        }        
        return array_diff_key($result + $query, array_flip($forbiddenOperators));//avoid operators like 'sort', 'skip', 'limit' to be queried
    }    
    
    /**
     * remove one document by "_id"
     * @param string $id
     * <p>
     * $options can be blow values<br />
     * "w" => The default value for MongoClient is 1. <br /><br />
     * "justOne" => Remove at most one record matching this criteria.
     * "fsync" => Boolean, defaults to FALSE. Forces the insert to be synced to disk before returning success. If TRUE, an acknowledged insert is implied and will override setting w to 0. <br /><br />
     * "timeout" => Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response.
     * If the database does not respond within the timeout period, a MongoCursorTimeoutException will be thrown.<br /><br />
     * "safe" => Deprecated. Please use the WriteConcern w option.     
     * </p> 
     * @return boolean true on success, otherwise false.
     * @throws Exception
     * @link http://php.net/manual/zh/mongocollection.save.php
     */    
    public function remove($id = null, $options = array()) {
        $allowOptions = empty($options) ? $options : array_intersect_key($options, array_flip(array('w', 'fsync', 'timeout', 'safe')));
        $allowOptions['justOne'] = true;//remove only one doc
        $mongoId = is_null($id) ? $this->_id : $id;
        $result = false;        
        if (is_scalar($mongoId) && ctype_alnum((string)$mongoId)) {
            try {
                $this->getCollection()->remove(
                    array('_id' => new MongoId((string)$mongoId)),
                    $allowOptions
                );
                $result = true;
            } catch (MongoCursorException $e) {
                throw new Exception($e->getMessage());
            }
            if ($mongoId == $this->_id) {
                $this->_id = null;
                $this->clean(); //if remove self, set object's properties null
            }
        } else {
            throw new Exception("id:{$id} is not alphanumeric and scalar");
        }
        return $result;
    }
    
    /**
     * remove documents by specified ids
     * @param array $ids an array includes string type ids
     * @return boolean ture on successed, otherwise false
     * @throws Exception
     */
    public function removeByIds(array $ids = array()) {
        $validIds = array_filter($ids, function($v) {
            return is_scalar($v) && ctype_alnum((string)$v) ? true : false;
        });
        $illegalIds = array_diff($ids, $validIds);
        $result = false;
        if (!empty($illegalIds)) throw new Exception('id:' . implode(', ', $illegalIds) . ' are not illegal');
        $validMongoIds = array_map(function($v) {
            return new MongoId($v);            
        }, $validIds);
        try {
            $this->getCollection()->remove(
                array(
                    '_id' => array('$in' => $validMongoIds),
                )
            );
            $result = true;
            if (in_array($this->_id, $validIds)) {
                $this->_id = null;
                $this->clean(); //clean self if $this->_id in $ids array
            }
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
        return $result;
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
        $allowOptions = empty($options) ? $options : array_intersect_key($options, array_flip(array('fsync', 'timeout', 'safe')));
        $allowOptions['w'] = 1;
        try {            
            $result = $this->validateFields()->getCollection()->save($this->parseProperty(true), $allowOptions);
            $this->increaseCounter();
            if ($result['updatedExisting'] == false && isset($result['upserted'])) {//upsert behavior get insertid to assing _id property
                $this->_id = (string)$result['upserted'];
            } 
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());//validation exception
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
            $fields = array_fill_keys($this->getFields(), true);
            foreach (array_chunk($mongoIds, 100) as $eachMongoIds) {
                $cursor = $this->getCollection()->find(array('_id' => array('$in' => $eachMongoIds)), $fields);//don't use $this->find() here, only '_id' will be used to query
                $this->increaseCounter();
                //$data = iterator_to_array($cursor);
                foreach ($cursor as $key => $value) {
                    $result[$key] = $this->parseDoc($value);
                }
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
        $allowOptions = !empty($options) ? array_intersect_key($options, array_flip(array('fsync', 'timeout', 'safe'))) : $options;
        $allowOptions['w'] = 1;
        $data = $this->parseProperty();        
        if (empty($data)) throw new Exception('insert data is empty');
        try {            
            $this->validateFields()->getCollection()->insert($data, $allowOptions);
            $this->increaseCounter();
            $this->_id = (string)$data['_id'];
        } catch (MongoCursorException $e) {
            throw new Exception($e->getMessage());
        } catch (MongoException $e) {
            throw new Exception($e->getMessage());
        }        
        return $this;
    }

    /**
     * Inserts multiple documents into this collection
     * @param array $docs an array which keys of each values must in keys of MongoData::$fields
     * @param array $options Options for the inserts.
     * <p>
     * $options can be blow values<br />
     * "w" => The default value for MongoClient is 1. <br /><br />
     * "fsync" => Boolean, defaults to FALSE. Forces the insert to be synced to disk before returning success. If TRUE, an acknowledged insert is implied and will override setting w to 0. <br /><br />
     * "timeout" => Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response.
     * If the database does not respond within the timeout period, a MongoCursorTimeoutException will be thrown.<br /><br />
     * "continueOnError" => Boolean, defaults to FALSE. If set, the database will not stop processing a bulk insert if one fails (eg due to duplicate IDs).
     * This makes bulk insert behave similarly to a series of single inserts, except that calling MongoDB::lastError() will have an error set if any insert fails, not just the last one. 
     * If multiple errors occur, only the most recent will be reported by MongoDB::lastError().
     * "safe" => Deprecated. Please use the WriteConcern w option.     
     * </p>      
     * @return array each objects in an array with '_id' field
     * @throws Exception
     * @link http://us2.php.net/manual/zh/mongocollection.batchinsert.php
     */
    public function batchInsert(array $docs = array(), array $options = array()) {
        $className = get_class($this);
        $validDocs = array_filter($docs, function($v) {
            return is_array($v) && !empty($v) ? true : false;
        });
        $result = array();
        if (!empty($validDocs)) {
            $allowOptions = empty($options) ? $options : array_intersect_key($options, array_flip(array('fsync', 'timeout', 'continueOnError', 'safe')));
            $allowOptions['w'] = 1;            
            $insertData = array();
            $fieldsMapping = $this->getFields();
            foreach ($validDocs as $key => $eachData) {
                foreach ($eachData as $k => $v) {
                    if (isset($fieldsMapping[$k])) {
                        $insertData[$key][$fieldsMapping[$k]] = $v;
                    }
                }
            }            
            try {
                $this->validateFieldsArray($insertData)->getCollection()->batchInsert($insertData, $allowOptions);
                foreach ($insertData as $eachData) {
                    $result[(string)$eachData['_id']] = $this->parseDoc($eachData);
                }
                $this->increaseCounter();
            } catch (MongoCursorException $e) {
                throw new Exception($e->getMessage());
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            throw new Exception('data going to be inserted are empty');
        }
        return $result;
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
            $allowOptions = empty($options) ? $options : array_intersect_key($options, array_flip(array('upsert', 'multiple', 'fsync', 'timeout', 'safe')));
            $allowOptions['w'] = 1;
            try {                
                $this->validateFields()->getCollection()->update($criteria, $this->parseProperty(), $allowOptions);
                $this->increaseCounter();
            } catch (Exception $e) {
                throw new $e->getMessage();
            } catch (MongoCursorException $e) {
                throw new Exception($e->getMessage());
            }           
        } else {
            throw new Exception("_id:{$this->_id} is not alphanumeric or scalar");
        }
        return $this;
    }
    
    /**
     * Performs an operation similar to SQL's GROUP BY command<br />
     * code sample:<br />
     * <pre>
     * $gourpUser = new User();
     * 
     * $ret = $gourpUser->group(
     *      array('name'),
     *      array('count' => 0, 'ageSum' => 0),
     *      'prev.count++;prev.ageSum += obj.age',
     * )
     * return array(
     *      0 => array(
     *          'name' => 'foo',
     *          'count' => '1',
     *          'ageSum' => '26',
     *      )
     *      1 => array(
     *          'name' => 'bar',
     *          'count' => '2',
     *          'ageSum' => '53',
     *      )
     *      2 => array(
     *          'name' => 'barz',
     *          'count' => '1',
     *          'ageSum' => '20',
     *      )
     * )
     * </pre>
     * @param array $keys Fields to group by. If an array or non-code object is passed, it will be the key used to group results.
     * @param array $initial Initial value of the aggregation counter object.     
     * @param string $reduce A function that takes two arguments (the current document "obj" and the aggregation to this point "prev") and does the aggregation.
     * @param array $options Optional parameters to the group command. Valid options include:<br /><br />
     * "condition" => Criteria for including a document in the aggregation.<br /><br />
     * "finalize" => Function called once per unique key that takes the final output of the reduce function.     
     * @return array Returns an array containing the result.
     * @link http://php.net/manual/zh/mongocollection.group.php
     */
    public function group(array $keys = array(), array $initial = array(), $reduce = '', array $options = array()) {                
        $reduceCode = new MongoCode("function (obj, prev) {{$reduce}}");
        $ret = $this->getCollection()->group(array_flip($keys), $initial, $reduceCode, $options);
        $this->increaseCounter();
        if ($ret['ok'] == 0 && isset($ret['errmsg'])) throw new Exception("errmsg:{$ret['errmsg']}");
        return $ret['retval'];
    }
    
    /**
     * 
     * @param array $data the document to be assign to the cloned object
     * @return \MongoData a cloned MongoData object
     */
    private function parseDoc(array $data) {        
        $o = clone $this;// use clone to keep Any properties that are references to other variables, such as MongoClient, MongoCollection, will remain references.                
        $o->_id = isset($data['_id']) ? (string)$data['_id'] : null;
        foreach ($this->getFields() as $property => $field) {            
            $o->{$property} = isset($data[$field]) ? $data[$field] : null;
        }        
        return $o;
    }
    
    /**
     * parse object mapping perporties to an array.
     * @param bool $_id if include _id field , 'true' means include 'false' means not
     * @return array an array with mongodb fields as keys
     */
    private function parseProperty($_id = false) {
        $data = $_id ? array('_id' => new MongoId($this->_id)) : array();
        foreach ($this->getFields() as $property => $field) $data[$field] = $this->{$property};
        return $data;
    }
    
    /**
     * return mongocuroser of this class
     * @return MongoCursor
     */
    private function getMongoCursor() {
        return self::$mongoCursor[get_class($this)];
    }
    
    /**
     * set mongocursor object to self::$mongoCursor array
     * @param MongoCursor $cursor
     * @return \MongoData
     */
    private function setMongoCursor(MongoCursor $cursor) {
        self::$mongoCursor[get_class($this)] = $cursor;
        return $this;
    }
    
    /**
     * get instance class's fields mapping
     * @return array collection's fields mapping array
     */
    private function getFields() {
        return self::$fields[__CLASS__][get_class($this)];
    }

    /**
     * init class if static vars are not set when unserialize, get from memcache, redis, .etc
     */
    public function __wakeup() {
        if (!isset(self::$fields[__CLASS__][get_class($this)])) $this->__construct();
    }

    public function current() {
        $result = $this->getMongoCursor() instanceof MongoCursor ? $this->parseDoc($this->getMongoCursor()->current()) : null;        
        return $result;
    }

    public function next() {
        return $this->getMongoCursor() instanceof MongoCursor ? $this->getMongoCursor()->next() : null;
    }

    public function key() {
        return $this->getMongoCursor() instanceof MongoCursor ? $this->getMongoCursor()->key() : null;
    }

    public function valid() {
        return $this->getMongoCursor() instanceof MongoCursor ? $this->getMongoCursor()->valid() : false;
    }

    public function rewind() {
        return $this->getMongoCursor() instanceof MongoCursor ? $this->getMongoCursor()->rewind() : null;
    }
     
}
?>
