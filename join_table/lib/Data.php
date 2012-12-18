<?php
//dengjing34@vip.qq.com
class Data {

	public $key, $table, $columns, $className;
	private $saveNeeds = array();
	private $connection;
	private $result;
	private $sql;
	private $appendWhere;
	public $attris = array();
	public static $counter = 0;

	public function init($options) {
		$className = get_class($this);
		$this->className = $className;
		$this->key = isset($options['key']) ? $options['key'] : NULL;
		$this->table = isset($options['table']) ? $options['table'] : NULL;
		$this->columns = isset($options['columns']) ? $options['columns'] : array();
		$this->saveNeeds = isset($options['saveNeeds']) ? $options['saveNeeds'] : array();
	}

	public function reset() {
		foreach ($this->columns as $objCol => $dbCol) {
			if (isset($this->$field)) {
				$this->$field = null;
			}
		}
	}

	public function load($value = null) {
		$key = $this->key;
		if (!is_null($value)) {
			$this->$key = $value;
		}
		if (is_null($this->$key))
			throw new Exception('No key has been set when load ' . $this->className, 111);
		$this->connection = DataConnection::getConnection();
		$this->$key = mysql_real_escape_string($this->$key, $this->connection);
		$columns = $this->columns;
		$colstr = '`' . implode('`, `', $columns) . '`';
		$where = "WHERE `{$columns[$key]}` = '{$this->$key}'";
		$this->sql = "SELECT {$colstr} FROM {$this->table} {$where} LIMIT 1";
		if ($this->query()) {
			$row = mysql_fetch_assoc($this->result);
		} else {
			$this->clean();
			throw new Exception("{$this->className}::{$this->$key} not found\n", 11);
		}
		$this->parseRow($this, $row, $columns);
		$this->clean();
        return $this;
	}

	protected function query() {
		self::$counter++;
		if ($this->result = mysql_query($this->sql, $this->connection)) {
			return mysql_affected_rows($this->connection);
		} else {
			$errno = mysql_errno($this->connection);
			if ($errno == '2006' || $errno == '2013') { // skip 'MySQL server has gone away' error
				$this->connection = DataConnection::getConnection();
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
		$columns = $this->columns;
		if ($rows) {
			$objClean = new $this->className;
			while ($row = mysql_fetch_assoc($this->result)) {
				$obj = clone $objClean;
				$this->parseRow($obj, $row, $columns);
				$obj->clean();
				if (is_null($obj->{$this->key})) {
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
		if (isset($this->columns[$name]) && isset($this->$name))
			return $this->$name;
		if (isset($this->attris[$name]) && isset($this->attris[$name]))
			return $this->attris[$name];
		return false;
	}

	public function set($name, $value) {
		$name = iconv('GBK', 'UTF-8', @iconv('UTF-8', 'GBK//IGNORE', $name));
		$value = iconv('GBK', 'UTF-8', @iconv('UTF-8', "GBK//IGNORE", $value));
		if (isset($this->columns[$name])) {
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
		$key = $this->key;
		if (!is_array($options)) {
			$options = array();
		}
		$columns = $this->columns;
		$this->connection = DataConnection::getConnection();
		$clauses = $this->clause(
				$options,
				array(
				    'limit' => 'LIMIT 1000',
				    'order' => "ORDER BY `{$columns[$key]}` DESC",
				)
		);
		$colstr = implode('`, `', $columns);
		$this->sql = "SELECT `{$colstr}` FROM `" . $this->table . "` {$clauses['index']} {$clauses['where']} {$clauses['order']} {$clauses['limit']}";
		return $this->query();
	}

	private function clause($options = array(), $clauses = array()) {
		$columns = $this->columns;

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
		$columns = $this->columns;
		$this->appendWhere .= " AND `{$columns[$column]}` {$expression}";
		return $this;
	}

	public function count($options = array()) {
		if (!is_array($options)) {
			$options = array();
		}
		$this->connection = DataConnection::getConnection();

		$clauses = $this->clause($options);

		$this->sql = "SELECT COUNT(1) FROM `" . $this->table . "` {$clauses['index']} {$clauses['where']}";

		$number = $this->query() ? current(mysql_fetch_assoc($this->result)) : null;

		$this->clean();
		return $number;
	}

	public function save() {
		if (!empty($this->saveNeeds)) {
			foreach ($this->saveNeeds as $o) {
				if (!$this->get($o) || strlen($this->get($o)) == 0) {
					throw new Exception($this->className . "的属性:$o 不能为空");
				}
			}
		}
		$key = $this->key;
		if (isset($this->$key) && strlen($this->$key) !== 0) {
			$this->update();
		} else {
			$this->insert();
		}
		$this->clean();
		return $this;
	}

	private function insert() {
		$this->connection = DataConnection::getConnection();
		$cols = array();
		$vals = array();
		$columns = $this->columns;
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

		$this->sql = "INSERT INTO `" . $this->table . "` ({$colstr}) VALUES ({$valstr})";
		$this->query();
		$key = $this->key;
		if (!isset($this->$key)) {
			$this->$key = mysql_insert_id($this->connection) or die(mysql_error($this->connection));
		}
	}

	private function update() {
		$key = $this->key;
		$sets = array();
		$columns = $this->columns;
		$this->connection = DataConnection::getConnection();
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
		$this->sql = "UPDATE `" . $this->table . "` SET {$setstr} WHERE {$where} LIMIT 1";
		$this->query();
	}

	public function delete($value = null) {
		$key = $this->key;
		if (!is_null($value)) {
			$this->$key = $value;
		}
		if (!isset($this->$key) || (isset($this->$key) && is_null($this->$key))) {
			throw new Exception("{$this->columns[$key]} is null when delete");
		}
		$this->connection = DataConnection::getConnection();
		$columns = $this->columns;
		$where = "WHERE {$columns[$key]} = '{$this->$key}'";
		$this->sql = "DELETE FROM `" . $this->table . "` {$where} LIMIT 1";
		$this->query();
		$this->clean();
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
		foreach ($this->columns as $objcol => $dbcol) {
			$this->$objcol = isset($this->$objcol) && !is_null($this->$objcol) ? htmlspecialchars($this->$objcol, ENT_NOQUOTES, 'ISO-8859-1', false) : NULL;
		}
		foreach ($this->attris as $key => $value) {
			$this->attris[$key] = is_null($value) ? null : htmlspecialchars($value, ENT_NOQUOTES, 'ISO-8859-1', false);
		}
	}

	/* Code Sample:
	 * $jubao = new Jubao();
	 * $jubao->whereAnd('insertedTime', '> 1234567890');
	 * $jubao->groupBy(
	 * 		array('cityEnglishName','firstCategoryEnglishName'),
	 * 		array('Count' => 'distinct id', 'Max' => 'id'), //key可以是Sum, Max, Min, Count, Avg 各种MySQL支持的聚合函数
	 * 		array('limit' => '10', 'order' => array('Count' => 'desc')) //如果要按Count结果排序，字段名就写Count
	 * );
	 *
	 * Return Sample:
	 * array(
	 * 	array('cityEnglishName' => 'shanghai', 'firstCategoryEnglishName'　=> 'fuwu', 'Count' => 20, 'Max' => 99999),
	 * 	array('cityEnglishName' => 'shanghai', 'firstCategoryEnglishName'　=> 'jiaoyou', 'Count' => 10, 'Max' => 88888),
	 * );
	 */

	public function groupBy($groupByFields, $countFields, $options = array()) {
		$this->connection = DataConnection::getConnection();
		$clauses = $this->clause($options, array('limit' => 'LIMIT 10', 'order' => ''));
		$groupBySql = count($groupByFields) ? implode(',', $groupByFields) : '';
		$countSql = '';
		foreach ($countFields as $func => $column) {
			$countSql .= "{$func}($column) AS {$func},";
		}
		$columns = $this->columns;
		$groupBySql = strtr($groupBySql, $columns);
		$countSql = strtr($countSql, $columns);
		$clauses['groupBy'] = count($groupByFields) ? 'GROUP BY ' . $groupBySql : '';
		$this->sql = "SELECT " . trim($groupBySql . ',' . $countSql, ',') . " FROM `" . $this->table . "` {$clauses['index']} {$clauses['where']} {$clauses['groupBy']} {$clauses['order']} {$clauses['limit']}";
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

	/*	 * ********************
	 * $obj = new class();
	 * $result = $obj->load(
	 *    array(
	 *      1=>1,
	 *      2=>2,
	 *      3=>3,
	 *    )
	 * );
	 * ******************** */

	public function loads($keys) {
		if (count($keys) == 0)
			return array();
		$key = $this->key;
		$columns = $this->columns;
		$objClean = new $this->className;
		//Get data from db
		$keyArrays = array_chunk(array_keys($keys), 50);
		$opts = array();
		foreach ($keyArrays as $ks) {
			$this->whereAnd($key, 'IN (' . implode(',', $ks) . ')');
			$opts['limit'] = count($ks);
			$opts['order'] = 'no';
			if ($rows = $this->getQuery($opts)) {
				while ($row = mysql_fetch_assoc($this->result)) {
					$_obj = clone $objClean;
					$this->parseRow($_obj, $row, $columns);
					$_obj->clean();
					$keys[$_obj->$key] = $_obj;
				}
			}
			$this->clean();
		}
		return array_filter($keys);
	}

}
?>
