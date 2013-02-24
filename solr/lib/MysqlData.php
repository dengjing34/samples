<?php

/**
 * ORM of mysql
 *
 * @author jingd <jingd3@jumei.com>
 */
class MysqlData {
    const MODE_MASTER = 'master', MODE_SLAVE = 'slave';
    const CACHE_PREFIX = 'mysql_';
    public $className;
    private $connection;
    private $result;
    private $sql;
    private $appendWhere;
    public $attris = array();
    public static $counter = 0;
    private static $connections = array(), $dbs = array(), $searcher = array();
    private static $columns = array(), $table = array(), $key = array(), $saveNeeds = array();

    /**
     * 初始化配置
     * @param array $options
     * @throws Exception 在config里面找不到默认的databse配置会抛异常
     */
    public function init(array $options) {
        $className = get_class($this);
        $this->className = $className;
        self::$key[$this->className] = isset($options['key']) ? $options['key'] : null;
        self::$table[$this->className] = isset($options['table']) ? $options['table'] : null;
        self::$columns[$this->className] = isset($options['columns']) ? $options['columns'] : array();
        self::$saveNeeds[$this->className] = isset($options['saveNeeds']) ? $options['saveNeeds'] : array();
        if (isset($options['db']) && $options['db']) {
            self::$dbs[$this->className] = $options['db'];
        } else {
            try {
                self::$dbs[$this->className] = Config::item('mysql.dbs.default');
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }            
        }        
        self::$searcher[$this->className] = isset($options['searcher']) && $options['searcher'] ? $options['searcher'] : null;
    }

    final public function key() {
        return self::$key[$this->className];
    }
    
    final public function columns() {
        return self::$columns[$this->className];
    }
    
    final public function table() {
        return self::$table[$this->className];
    }
    
    final private function saveNeeds() {
        return self::$saveNeeds[$this->className];
    }
    
    /**
     * 获取mysql的connection资源
     * @param string $node master或slave节点
     * @return resource a MySQL link identifier
     * @throws Exception 找不到配置的mysql节点的时候抛出异常
     */
    private function getConnection($node = self::MODE_SLAVE) {
        if (!isset(self::$connections[$node])) {
            try {
                $config = Config::item("mysql.{$node}");
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
            self::$connections[$node] = mysql_connect($config['host'], $config['user'], $config['password']) or die(mysql_error());            
            mysql_select_db(self::$dbs[$this->className]) or die(mysql_error());
            mysql_query('set names utf8') or die(mysql_error());
        }
        return self::$connections[$node];
    }
    
    public function reset() {
        foreach ($this->columns() as $objCol => $dbCol) {
            if (isset($this->$field)) {
                $this->$field = null;
            }
        }
    }
    
    /**
     * 生成mysql类的memcache key
     * @param string|array $key 要存储的key,可以是数组或字符,不能为空
     * @return string|null
     */
    private function cacheKey($key) {
        $prefix = self::CACHE_PREFIX . self::$dbs[$this->className] . "_{$this->className}_";
        if (is_scalar($key) && $key) {
            return $prefix . $key;
        } elseif (is_array($key) && !empty($key)) {
            return array_map(function($v) use($prefix) {
                return $prefix . $v;
            }, $key);
        } else {
            return null;
        }  
    }
    
    /**
     * 获取Cache的实例
     * @return Cache
     */
    private function cacheInstance() {
        return Cache::instance();
    }
    
    private function cacheGet($key) {        
        return $this->cacheInstance()->get($this->cacheKey($key));
    }
    
    private function cacheSet($key, $value) {        
        return $this->cacheInstance()->set($this->cacheKey($key), $value);
    }
    
    private function cacheDel($key) {
        return $this->cacheInstance()->delete($this->cacheKey($key));
    }
    
    private function cacheGetMulti($keys) {
        return $this->cacheInstance()->getMulti($this->cacheKey($keys));
    }

    /**
     * 获取init()指定的searcher实例 若init()没有指定searcher或指定的searcher不是继承于Searcher类则返回null
     * @return Searcher 未指定或制定的searcher不是继承于MysqlData的类会返回null
     */
    private function getSearcher() {        
        if (self::$searcher[$this->className] instanceof Searcher) {
            return self::$searcher[$this->className];
        } elseif (!is_null(self::$searcher[$this->className]) && class_exists(self::$searcher[$this->className])) {
            $searcher = new self::$searcher[$this->className]();
            self::$searcher[$this->className] = $searcher instanceof Searcher ? $searcher : null;
            return self::$searcher[$this->className];
        } else {
            return null;
        }        
    }
    
    /**
     * 通过init()指定的searcher来更新solr  
     * @return boolean 没有指定searcher或更新失败返回false
     * @throws Exception 更新失败会抛出异常并说明原因
     */
    private function searcherUpdate() {
        $searcher = $this->getSearcher();
        if (!is_null($searcher)) {
            $data = $this->parseSearcherData();            
            if (!empty($data)) {
                $result = $searcher->update($data);                
                if (!$result) {
                    $error = $searcher->lastError();
                    throw new Exception($error['error']['msg'], $error['error']['code']);                    
                }
                return $result;
            }
        }
        return false;
    }
    
    /**
     * 通过init()指定的searcher来删除solr的数据
     * @return boolean 成功返回true,失败抛出异常,没有key直接返回false
     * @throws Exception 删除solr中的数据失败的时候会抛出异常并说明原因
     */
    private function searcherDelete() {
        $key = $this->key();
        $id = $this->{$key};
        if (ctype_digit((string)$id) && !is_null($searcher = $this->getSearcher())) {            
            $result = $searcher->delete($id);
            if (!$result) {
                $error = $searcher->lastError();
                throw new Exception($error['error']['msg'], $error['error']['code']);    
            }
            return $result;
        }
        return false;
    }


    /**
     * 解析需要提交到solr里面的数据 包括columns()和attris的属性
     * @return array 解析后的数组
     */
    private function parseSearcherData() {
        $result = array();
        foreach (array_keys($this->columns()) as $property) {
            $result[$property] = $this->{$property};
        }
        foreach ($this->attris as $key => $val) {
            $result[$key] = $val;
        }
        return $result;
    }
    
    public function load($value = null) {
        $key = $this->key();
        if (!is_null($value)) {
            $this->$key = $value;
        }
        $columns = $this->columns();
        if (is_null($this->{$key}))
            throw new Exception('No key has been set when load ' . $this->className, 111);
        $row = false;
        if (($row = $this->cacheGet($this->{$key}))) {
            foreach ($columns as $objCol => $dbCol) {
                $this->{$objCol} = $row->{$objCol};
            }
            if (isset($columns['attributeData'])) {
                $this->attris = $row->attris;
            }            
        } else {
            $this->connection = $this->getConnection();
            $this->$key = mysql_real_escape_string($this->$key, $this->connection);        
            $colstr = '`' . implode('`, `', $columns) . '`';
            $where = "WHERE `{$columns[$key]}` = '{$this->$key}'";
            $this->sql = "SELECT {$colstr} FROM {$this->table()} {$where} LIMIT 1";
            if ($this->query()) {
                $row = mysql_fetch_assoc($this->result);
            } else {
                $this->clean();
                throw new Exception("{$this->className}::{$this->$key} not found\n", 11);
            }
            $this->parseRow($this, $row, $columns);
            $this->clean();
            $this->cacheSet($this->{$key}, $this);
        }
        unset($row);
        return $this;
    }

    protected function query() {
        self::$counter++;
        if (($this->result = mysql_query($this->sql, $this->connection))) {
            return mysql_affected_rows($this->connection);
        } else {
            $errno = mysql_errno($this->connection);
            if ($errno == '2006' || $errno == '2013') { // skip 'MySQL server has gone away' error
                $this->connection = $this->getConnection();
                $this->query();
            } else {
                throw new Exception("There's something wrong with the sql! " . $this->sql, 22);
            }
        }
    }

    public function parseRow($_obj, $row, $columns) {
        foreach ($columns as $objcol => $dbcol) {
            if ($objcol != 'attributeData') {
                $_obj->$objcol = $row[$dbcol];
                continue;
            }

            preg_match_all("/([^:]+):(.*)\n/", rtrim($row[$dbcol]) . "\n", $matches);
            $attrs = array();
            foreach ($matches[1] as $attKey => $attrName) {
                $attrs[$attrName] = $matches[2][$attKey];
            }
            $_obj->attris = str_replace(array('%%', '%n'), array("%", "\n"), $attrs);
            unset($attrs, $matches);
            $_obj->attributeData = null;
        }
        return $_obj;
    }

    protected function clean() {
        $this->sql = null;
        $this->result = null;
        $this->connection = null;
        $this->appendWhere = null;
    }

    public function find($options = array()) {
        $rows = $this->getQuery($options);
        $objs = array();
        $columns = $this->columns();
        if ($rows) {
            $objClean = new $this->className;
            while ($row = mysql_fetch_assoc($this->result)) {
                $obj = clone $objClean;
                $this->parseRow($obj, $row, $columns);
                $obj->clean();
                if (is_null($obj->{$this->key()})) {
                    //Email::sendMail('arch@baixing.com', PHP_SAPI .' Find出空数据了@'.date('c'), $this->className."\n".print_r($this,true).print_r($obj,true).print_r($row,true));
                    continue;
                }
                $objs[] = $obj;
                unset($row);
            }
        }
        $this->clean();
        return $objs;
    }

    public function get($name) {
        $columns = $this->columns();
        if (isset($columns[$name]) && isset($this->$name))
            return $this->$name;
        if (isset($this->attris[$name]) && isset($this->attris[$name]))
            return $this->attris[$name];
        return false;
    }

    public function set($name, $value) {
        $name = iconv('GBK', 'UTF-8', @iconv('UTF-8', 'GBK//IGNORE', $name));
        $value = iconv('GBK', 'UTF-8', @iconv('UTF-8', "GBK//IGNORE", $value));
        $columns = $this->columns();
        if (isset($columns[$name])) {
            $this->$name = $value;
            return $this;
        }
        if (strlen($name) == 0)
            return;
        $this->attris[$name] = $value;
        return $this;
    }

    public function del($name) {
        unset($this->attris[$name]);
        return $this;
    }

    private function getQuery($options = array()) {
        $key = $this->key();
        if (!is_array($options)) {
            $options = array();
        }
        $columns = $this->columns();
        $this->connection = $this->getConnection();
        $clauses = $this->clause(
                $options, array(
            'limit' => 'LIMIT 1000',
            'order' => "ORDER BY `{$columns[$key]}` DESC",
                )
        );
        $colstr = implode('`, `', $columns);
        $this->sql = "SELECT `{$colstr}` FROM `" . $this->table() . "` {$clauses['index']} {$clauses['where']} {$clauses['order']} {$clauses['limit']}";
        return $this->query();
    }

    private function clause($options = array(), $clauses = array()) {
        $columns = $this->columns();

        if (!isset($clauses['where']))
            $clauses['where'] = 'WHERE 1 = 1';
        if (!isset($clauses['index']))
            $clauses['index'] = '';
        if (!isset($clauses['limit']))
            $clauses['limit'] = 'LIMIT 1';

        foreach ($columns as $objcol => $dbcol) {
            if (isset($this->$objcol) && !is_null($this->$objcol)) {
                $value = mysql_real_escape_string($this->$objcol, $this->connection);
                $clauses['where'] .= " AND `{$columns[$objcol]}` = '{$value}'";
            }
        }

        if (isset($options['whereAnd']) && is_array($options['whereAnd'])) {
            foreach ($options['whereAnd'] as $expr)
                $this->whereAnd($expr[0], $expr[1]);
        }

        if (!empty($this->appendWhere)) {
            $clauses['where'] .= $this->appendWhere;
        }

        if (isset($options['limit'])) {
            $clauses['limit'] = "LIMIT {$options['limit']}";
        }

        if (isset($options['useIndex'])) {
            $clauses['index'] = "FORCE INDEX ({$options['useIndex']})";
        }

        if (isset($options['order']) && is_array($options['order'])) {
            $ords = array();
            foreach ($options['order'] as $objcol => $sort)
                $ords[] = "`{$objcol}` {$sort}";
            $clauses['order'] = strtr("ORDER BY " . implode(', ', $ords), $columns);
        }

        if (isset($options['order']) && $options['order'] == 'no') {
            $clauses['order'] = "";
        }

        $clauses['where'] = str_replace('1 = 1 AND ', '', $clauses['where']);

        return $clauses;
    }

    public function whereAnd($column, $expression) {
        $columns = $this->columns();
        $this->appendWhere .= " AND `{$columns[$column]}` {$expression}";
        return $this;
    }

    public function count($options = array()) {
        if (!is_array($options)) {
            $options = array();
        }
        $this->connection = $this->getConnection();

        $clauses = $this->clause($options);

        $this->sql = "SELECT COUNT(1) FROM `" . $this->table() . "` {$clauses['index']} {$clauses['where']}";

        $number = $this->query() ? current(mysql_fetch_assoc($this->result)) : null;

        $this->clean();
        return $number;
    }

    /**
     * 有key的时候是update,没有key的时候是insert
     * @return \MysqlData
     * @throws Exception init()指定的saveNeeds字段有空值的时候会抛出异常
     */
    public function save() {
        $saveNeeds = $this->saveNeeds();
        if (!empty($saveNeeds)) {
            foreach ($saveNeeds as $o) {
                if (!$this->get($o) || strlen($this->get($o)) == 0) {
                    throw new Exception($this->className . "的属性:$o 不能为空");
                }
            }
        }
        $key = $this->key();
        if (isset($this->{$key}) && strlen($this->{$key}) !== 0) {
            $this->update();            
            $this->cacheDel($this->{$key});
        } else {
            $this->insert();
        }
        $this->searcherUpdate();//更新solr
        $this->clean();        
        return $this;
    }

    private function insert() {
        $this->connection = self::getConnection(self::MODE_MASTER);
        $cols = array();
        $vals = array();
        $columns = $this->columns();
        if (isset($columns['attributeData']))
            $this->attributeData = $this->getAttributeData();
        foreach ($columns as $objcol => $dbcol) {
            if (isset($this->$objcol) && !is_null($this->$objcol)) {
                $cols[] = "`{$dbcol}`";
                $value = mysql_real_escape_string($this->$objcol, $this->connection);
                $vals[] = "'{$value}'";
            }
        }

        $colstr = implode(', ', $cols);
        $valstr = implode(', ', $vals);

        $this->sql = "INSERT INTO `" . $this->table() . "` ({$colstr}) VALUES ({$valstr})";
        $this->query();
        $key = $this->key();
        if (!isset($this->{$key})) {
            $this->{$key} = mysql_insert_id($this->connection) or die(mysql_error($this->connection));
        }
    }

    private function update() {
        $key = $this->key();
        $sets = array();
        $columns = $this->columns();
        $this->connection = self::getConnection(self::MODE_MASTER);
        if (isset($columns['attributeData']))
            $this->attributeData = $this->getAttributeData();
        foreach ($columns as $objcol => $dbcol) {
            if ($objcol == $key)
                continue;
            if (is_null($this->$objcol)) {
                $sets[] = "`{$dbcol}` = null";
            } else {
                $value = mysql_real_escape_string($this->$objcol, $this->connection);
                $sets[] = "`{$dbcol}` = '{$value}'";
            }
        }
        $setstr = implode(', ', $sets);
        $where = "`{$columns[$key]}` = '{$this->$key}'";
        $this->sql = "UPDATE `" . $this->table() . "` SET {$setstr} WHERE {$where} LIMIT 1";
        $this->query();
    }

    public function delete($value = null) {
        $key = $this->key();
        if (!is_null($value)) {
            $this->$key = $value;
        }
        $columns = $this->columns();
        if (!isset($this->$key) || (isset($this->$key) && is_null($this->$key))) {
            throw new Exception("{$columns[$key]} is null when delete");
        }
        $this->connection = self::getConnection(self::MODE_MASTER);
        
        $where = "WHERE {$columns[$key]} = '{$this->{$key}}'";
        $this->sql = "DELETE FROM `" . $this->table() . "` {$where} LIMIT 1";
        $this->query();
        $this->clean();        
        $this->cacheDel($this->{$key});//delete memcache
        $this->searcherDelete();//delete solr
        return $this;
    }

    private function getAttributeData() {
        $attris = str_replace(array("%", "\n"), array('%%', '%n'), $this->attris);
        $ret = '';
        foreach ($attris as $eachKey => $eachValue) {
            $ret .= trim("$eachKey:$eachValue") . "\n";
        }
        return $ret;
    }

    public function htmlspecialchars() {
        foreach ($this->columns() as $objcol => $dbcol) {
            $this->$objcol = isset($this->$objcol) && !is_null($this->$objcol) ? htmlspecialchars($this->$objcol, ENT_NOQUOTES, 'ISO-8859-1', false) : NULL;
        }
        foreach ($this->attris as $key => $value) {
            $this->attris[$key] = is_null($value) ? null : htmlspecialchars($value, ENT_NOQUOTES, 'ISO-8859-1', false);
        }
    }

    /**
     * 执行mysql的group by 查询
     * @param array $groupByFields 需要group的字段构成的数组
     * @param array $countFields 一些需要聚合函数的字段构成的数组
     * @param array $options limit, order等条件
     * @return array 查询结果
     * <pre>
     * $jubao = new Jubao();
     * $jubao->whereAnd('insertedTime', '> 1234567890');
     * $jubao->groupBy(
     *     array('cityEnglishName','firstCategoryEnglishName'),
     *     array('Count' => 'distinct id', 'Max' => 'id'), //key可以是Sum, Max, Min, Count, Avg 各种MySQL支持的聚合函数
     *     array('limit' => '10', 'order' => array('Count' => 'desc')) //如果要按Count结果排序，字段名就写Count
     * );
     *
     * Return Sample:
     * array(
     *     array('cityEnglishName' => 'shanghai', 'firstCategoryEnglishName'　=> 'fuwu', 'Count' => 20, 'Max' => 99999),
     *     array('cityEnglishName' => 'shanghai', 'firstCategoryEnglishName'　=> 'jiaoyou', 'Count' => 10, 'Max' => 88888),
     * );
     * </pre>
     */
    public function groupBy(array $groupByFields, array $countFields, array $options = array()) {
        $this->connection = $this->getConnection();
        $clauses = $this->clause($options, array('limit' => 'LIMIT 10', 'order' => ''));
        $groupBySql = count($groupByFields) ? implode(',', $groupByFields) : '';
        $countSql = '';
        foreach ($countFields as $func => $column) {
            $countSql .= "{$func}($column) AS {$func},";
        }
        $columns = $this->columns();
        $groupBySql = strtr($groupBySql, $columns);
        $countSql = strtr($countSql, $columns);
        $clauses['groupBy'] = count($groupByFields) ? 'GROUP BY ' . $groupBySql : '';
        $this->sql = "SELECT " . trim($groupBySql . ',' . $countSql, ',') . " FROM `" . $this->table() . "` {$clauses['index']} {$clauses['where']} {$clauses['groupBy']} {$clauses['order']} {$clauses['limit']}";
        $flipColumns = array_flip($columns);
        $objs = array();
        if ($this->query()) {
            $objClean = new $this->className;
            while ($row = mysql_fetch_assoc($this->result)) {
                $obj = array();
                foreach ($row as $key => $value) {
                    if (isset($flipColumns[$key]))
                        $key = $flipColumns[$key];
                    $obj[$key] = $value;
                }
                $objs[] = $obj;
                unset($row);
            }
        }
        $this->clean();
        return $objs;
    }

    /**
     * 根据ids来获取数据
     * @param array $keys 需要获取的id数组,如array(1, 2, 3)
     * @return array 返回能够查询到的数据构成的数组,没有查询到的id不会包含在其中
     */
    public function loads($keys) {        
        $keys = array_filter(array_combine($keys, $keys), function($v) {
           return ctype_digit((string)$v) && $v > 0 ? true : false; 
        });
        if (count($keys) == 0)
            return array();        
        $key = $this->key();
        $columns = $this->columns();
        $objClean = new $this->className;
        //get data from memcache
        $data = array_combine(
                array_keys($keys),                
                $this->cacheGetMulti(array_keys($keys))
        );        
        //Get data from db
        $keyArrays = array_chunk(array_keys(array_filter($data, function($v) {
            return !is_object($v);
        })), 50);
        $opts = array();
        foreach ($keyArrays as $ks) {
            $this->whereAnd($key, 'IN (' . implode(',', $ks) . ')');
            $opts['limit'] = count($ks);
            $opts['order'] = 'no';
            if (($rows = $this->getQuery($opts))) {
                while ($row = mysql_fetch_assoc($this->result)) {
                    $_obj = clone $objClean;
                    $this->parseRow($_obj, $row, $columns);
                    $_obj->clean();
                    $data[$_obj->{$key}] = $_obj;
                    //store to memcache                    
                    $this->cacheSet($_obj->{$key}, $_obj);
                }
            }
            $this->clean();
        }
        return array_filter($data, function($v) {
            return is_object($v);
        });
    }
    
	public function  __wakeup() {
		if (!isset(self::$connections[$this->className])) $this->__construct();
	}
    
    /**
     * 返回已经执行过的mysql数据库查询次数
     * @return int 数据库查询的次数
     */
    public function counter() {
        return self::$counter;
    }
}

?>
