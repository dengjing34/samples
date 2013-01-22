<?php
/**
 * Description of Searcher
 *
 * @author jingd <jingd3@jumei.com>
 */
abstract class Searcher {
    const HOST_MASTER = 'master';
    const HOST_SLAVE = 'slave';
    const URI_QUERY = 'select';
    const URI_UPDATE = 'update';
    const MAX_ROWS = 1000;
    const DEFAULT_WT = 'json';
    const DEFAULT_QUERY = '*:*';
    const DEFAULT_ROWS = 10;    
    private static $host = array(), $cores = array(), $counter = 0;
    private $q = self::DEFAULT_QUERY, $fq = array(), $sort = array();
    
    private function __construct() {
        ;
    }
    
    protected function init(array $options = array()) {
        $required = array_flip(array(
            'core',
        ));
        if (count($diff = array_diff_key($required, $options)) > 0) {
            throw new Exception('key [' . implode('],[', array_keys($diff)) . '] must be specified in options');
        }        
        self::$cores[$this->className()] = $options['core'];//core name        
        return $this;
    }
    
    /**
     * 获取实例化的类名称
     * @return string 实例化的类名称
     */
    private function className() {
        return get_class($this);
    }
    
    /**
     * 获取设置的solr的core名称
     * @return string solr core的名称
     */
    protected function coreName() {
        return self::$cores[$this->className()];
    }

    /**
     * 
     * @param string $url solr的请求地址
     * @param boolean $waitSuccess 是否等待到成功 最多等待25秒
     * @param array $postData 需要提交的数据
     * @return boolean|json 失败返回false,成功返回请求的结果,默认是根据self::DEFAULT_WT来返回json数据
     */
    private function request($url, $waitSuccess = false, array $postData = array()) {        
        $timeout = $waitSuccess ? 25 : 1;//if $waitSuccess timeout after 25s, otherwise timeout after 1s
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);//if get binary data need to set header false
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }        
        $body = curl_exec($ch);
        $this->increaseCounter();
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        if ($body === false || $info['http_code'] != 200 || $error != '') {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $body;
    }       
    
    /**
     * 增加请求solr的次数
     * @return \Searcher
     */
    private function increaseCounter() {
        self::$counter++;
        return $this;
    }

    /**
     * 搜索df(default search field)字段的关键词 为空的话则使用*:*
     * @param string $string
     * @return \Searcher
     */
    public function defaultQuery($string = self::DEFAULT_QUERY) {
        $string = strlen(trim($string)) == 0 || $string == self::DEFAULT_QUERY ? self::DEFAULT_QUERY : $this->solrEscape($string);
        $this->q = $string;
        return $this;
    }
    
    /**
     * 添加fq(filter query)的查询
     * @param string $field
     * @param string $value
     * @return \Searcher
     */
    public function query($field, $value) {
        if (strlen(trim($field)) !=0 && strlen(trim($value)) != 0) {            
            $this->fq[] = "{$field}:{$value}";
        }        
        return $this;
    }
    
    /**
     * 对某个字段进行范围搜索
     * @param string $field 搜索的字段名
     * @param string $lower 搜索范围开始值
     * @param string $upper 搜索范围结束值
     * @return \Searcher
     */
    public function rangeQuery($field, $lower = '*', $upper = '*') {
        if (strlen(trim($field)) != 0) {
            $this->query($field, "[{$lower} TO {$upper}]");
        }        
        return $this;
    }
    
    /**
     * 搜索某一整天时间段的结果 查询字段需要是索引的date或tdate类型
     * @param string $field 查询字段 其在solr中的fieldType需要是date或tdate类型 如2000-01-01T01:01:01Z
    * @param string $value 日期如yyyy-mm-dd的格式,会自动补上当天开始和结束时分秒的值
     * @return \Searcher
     */
    public function dateQuery($field, $value) {
        if (preg_match('/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$/', $value)) {
            $this->query($field, "[{$value}T00:00:00Z TO {$value}T23:59:59Z]");
        }
        return $this;
    }
    
    /**
     * 搜索某一段时间范围的结果 查询字段需要是索引的date或tdate类型
     * @param string $field 查询字段 其在solr中的fieldType需要是date或tdate类型 如2000-01-01T01:01:01Z
     * @param type $startDateTime 时间范围开始值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*为不限
     * @param type $endDateTime 时间范围结束值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*为不限
     * @return \Searcher
     */
    public function dateRangeQuery($field, $startDateTime = '*', $endDateTime = '*') {
        $pattern = '/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$|^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])\s([01][0-9]|2[0123]):[0-5][0-9](:[0-5][0-9])?$|^\*$/';        
        if (preg_match($pattern, $startDateTime, $m) && preg_match($pattern, $endDateTime)) {            
            $startDateTime = $startDateTime != '*' ? $this->formatDateTime($startDateTime) : $startDateTime;
            $endDateTime = $endDateTime != '*' ? $this->formatDateTime($endDateTime) : $endDateTime;
            $this->rangeQuery($field, $startDateTime, $endDateTime);            
        }
        return $this;
    }
    
    /**
     * 搜索某一段时间范围的结果 查询字段需要是索引的timestamp
     * @param type $field 查询字段 其在solr中的fieldType需要是int或tint等数字类型 如1325350861
     * @param type $startDateTime 时间范围开始值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*为不限
     * @param type $endDateTime 时间范围结束值,支持yyyy-mm-dd | yyyy-mm-dd hh:mm | yyyy-mm-dd hh:mm:ss 的格式 用*为不限
     * @return \Searcher
     */
    public function timestampRangeQuery($field, $startDateTime = '*', $endDateTime = '*') {
        $pattern = '/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$|^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])\s([01][0-9]|2[0123]):[0-5][0-9](:[0-5][0-9])?$|^\*$/';
        if (preg_match($pattern, $startDateTime) && preg_match($pattern, $endDateTime)) {
            $startDateTime = $startDateTime != '*' ? strtotime($startDateTime) : $startDateTime;
            $endDateTime = $endDateTime != '*' ? strtotime($endDateTime) : $endDateTime;
            $this->rangeQuery($field, $startDateTime, $endDateTime);
        }
        return $this;
    }

    /**
     * 搜索某一整天时间段的结果 查询字段需要是索引的int或tint类型
     * @param string $field 查询字段 其在solr中的fieldType需要是int或tint类型 如1325350861
    * @param string $value 日期如yyyy-mm-dd的格式,会自动补上当天开始和结束时分秒的值
     * @return \Searcher
     */    
    public function timestampQuery($field, $value) {
        if (preg_match('/^\d{4}-([0][1-9]|1[012])-([012][0-9]|3[01])$/', $value)) {
            $this->query($field, '[' . strtotime("{$value} 00:00:00") . ' TO ' . strtotime("{$value} 23:59:59") . ']');
        }
        return $this;        
    }
    
    /**
     * 搜索查询字段包括一系列值
     * @param string 查询的字段
     * @param array $values 查询字段包括的一系列值
     * @return \Searcher
     */
    public function inQuery($field, array $values = array()) {
        foreach ($values as $eachValue) {
            $this->query($field, $eachValue);
        }
        return $this;
    }
    
    /**
     * 生成fq[filter query]的查询字符
     * @return string
     */
    private function parseFq() {        
        return implode(' ', $this->fq);
    }
    
    
    /**
     * 格式化时间为solr的date类型时间
     * @param string|int $date 可以是时间戳或YYYY-MM-DD | YYYY-MM-DD HH:MM | YYYY-MM-DD HH:MM:SS
     * @return string solr的date类型的查询格式 如 2012-12-13T12:13:14Z
     */
    public function formatDateTime($date) {
        if (ctype_digit((string)$date)) {
            return date('Y-m-d\TH:i:s\Z', $date);
        }
        return date('Y-m-d\TH:i:s\Z', strtotime($date));
    }
    
    /**
     * 过滤q(default query)的查询字符
     * @param type $value 查询的字符
     * @return string 过滤后的查询字符
     */
    public function solrEscape($value) {        
        $pattern = '/(\+|-|&&|\|\||!|\s|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\\\)/';
        return preg_replace($pattern, '\\\$1', $value);        
    }


    public function update() {
        
    }
    
    /**
     * 根据q[default query], fq[filter query], sort 来搜索得出结果
     * @param array $options 搜索选项可有的参数
     * <pre>
     * <code>
     * $options = array(
     *     'waitSuccess' => false,//是否等待成功 最多等待25秒
     *     'rows' => 10,//返回行数
     *     'page' => 1,//页数
     * );
     * <code>
     * <pre>
     * @return array 查询结果 
     * <pre>
     * <code>
     * $result = array(
     *     'currentPage' => 1,//当前页
     *     'totalPage' => 224,//总页数
     *     'pageSize' => 10,//每页记录数
     *     'numFound' => 2233,//总记录数
     *     'start' => 0,//跳过条数
     *     'docs' => array(
     *         0 => array(
     *             'id' => 1,
     *             ...
     *         ),
     *         1 => array(
     *             'id' => 2,
     *             ...
     *         ),
     *         ...
     *     ),
     * );
     * </code>
     * </pre>
     */
    public function search(array $options = array()) {
        $result = false;
        $q = $this->q;
        $options['fq'] = $this->parseFq();
        $options['sort'] = $this->parseSort();
        $options['rows'] = isset($options['rows']) ? $this->validRows($options['rows']) : self::DEFAULT_ROWS;
        $options['page'] = isset($options['page']) && ctype_digit((string)$options['page']) && $options['page'] > 0 ? $options['page'] : 1;
        $options['start'] = ($options['page'] - 1) * $options['rows'];
        $waitSuccess = isset($options['waitSuccess']) ? true : false;
        if ($data = $this->request($this->buildUrl($q, $options), $waitSuccess)) {
            $data = json_decode($data, true);
            $result['currentPage'] = $options['page'];
            $result['totalPage'] = ceil($data['response']['numFound'] / $options['rows']);
            $result['pageSize'] = $options['rows'];            
            $result += $data['response'];
        }
        return $result;
    }        
    
    /**
     * 构建请求的url地址
     * @param string $q
     * @param array $options
     * @return type
     */
    private function buildUrl($q = '', array $options = array()) {
        $params = array(
            'omitHeader' => 'true',//忽略请求状态和时间
            'q' => strlen(trim($q)) == 0 ? self::DEFAULT_QUERY : $q,            
            'wt' => self::DEFAULT_WT,            
            'rows' => $options['rows'],
            'start' => $options['start'],
        );
        if (isset($options['sort'])) $params['sort'] = $options['sort'];
        if (!empty($options['fq'])) $params['fq'] = $options['fq'];        
        return "{$this->slaveCore()}?" . http_build_query($params);
    }


    /**
     * 限制solr一次请求最返回的行数
     * @param int $rows 实际请求的返回行数
     * @return int 允许返回的行数
     */
    private function validRows($rows) {
        return ctype_digit((string)$rows) && $rows > 0 && $rows <= self::MAX_ROWS ? $rows : self::MAX_ROWS;
    }
   
    public function sort(array $sorts = array()) {
        $allowed = array('asc', 'desc');
        foreach ($sorts as $field => $sort) {
            $sort = strtolower($sort);
            if (strlen($field) != 0 && in_array($sort, $allowed)) {
                $this->sort[$field] = $sort;
            }                
        }
        return $this;
    }
    
    public function parseSort() {
        return implode(' ', array_map(function($field, $sort) {
            return "{$field} {$sort}";
        }, array_keys($this->sort), $this->sort));                    
    }
    
    private function host() {
        if (empty(self::$host)) {
            try {
                self::$host = Config::item('solr');
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }            
        }        
        return self::$host;
    }
    
    private function master() {
        $host = $this->host();
        if (isset($host[self::HOST_MASTER]['host'])) {
            return $host[self::HOST_MASTER]['host'];
        } else {
            throw new Exception("searcher master is undefined");
        }
    }        

    private function slave() {
        $host = $this->host();
        if (isset($host[self::HOST_SLAVE]['host'])) {
            return $host[self::HOST_SLAVE]['host'];
        } else {
            throw new Exception("searcher slave is undefined");
        }
    }
    
    private function masterCore() {
        return "{$this->master()}{$this->coreName()}/";
    }
    
    private function slaveCore() {
        return "{$this->slave()}{$this->coreName()}/" . self::URI_QUERY;
    }
    
    
}

?>
