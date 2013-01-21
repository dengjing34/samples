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
    const DEFAULT_START = 0;
    private static $host = array(), $cores = array(), $counter = 0;
    public $query = array();
    
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
    
    private function className() {
        return get_class($this);
    }
    
    protected function coreName() {
        return self::$cores[$this->className()];
    }

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
    
    private function increaseCounter() {
        self::$counter++;
        return $this;
    }
    
    public function query($field, $value) {
        $this->query[] = "{$field}:{$value}";
        return $this;
    }
    
    public function rangeQuery($field, $lower = '*', $upper = '*') {
        $this->query($field, "[{$lower} TO {$upper}]");
        return $this;
    }
    
    public function dateQuery($field, $value) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->query($field, "[{$value}T00:00:00Z TO {$value}T23:59:59Z]");
        }
        return $this;
    }
    
    public function dateRangeQuery($field, $startDate = '*', $endDate = '*') {
        $pattern = '/^(\d{4}-\d{2}-\d{2}|\*)$/';
        if (preg_match($pattern, $startDate) && preg_match($pattern, $endDate)) {
            $startDateTime = $startDate != '*' ? "{$startDate}T00:00:00Z" : $startDate;
            $endDateTime = $endDate != '*' ? "{$endDate}T23:59:59Z" : $endDate;
            $this->rangeQuery($field, $startDateTime, $endDateTime);
        }
        return $this;
    }
    
    public function dateTimeRangeQuery($field, $startDateTime = '*', $endDateTime = '*') {
        $pattern = '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}|\d{4}-\d{2}-\d{2}|\*)$/';        
        if (preg_match($pattern, $startDateTime) && preg_match($pattern, $endDateTime)) {
            $startDateTime = $startDateTime != '*' ? $this->formatDateTime($startDateTime) : $startDateTime;
            $endDateTime = $endDateTime != '*' ? $this->formatDateTime($endDateTime) : $endDateTime;
            $this->rangeQuery($field, $startDateTime, $endDateTime);            
        }
        return $this;
    }
    
    public function timestampRangeQuery($field, $startDateTime = '*', $endDateTime = '*') {
        $pattern = '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}|\d{4}-\d{2}-\d{2}|\*)$/';
        var_dump(preg_match($pattern, $startDateTime));
        if (preg_match($pattern, $startDateTime) && preg_match($pattern, $endDateTime)) {
            $startDateTime = $startDateTime != '*' ? strtotime($startDateTime) : $startDateTime;
            $endDateTime = $endDateTime != '*' ? strtotime($endDateTime) : $endDateTime;
            $this->rangeQuery($field, $startDateTime, $endDateTime);
        }
        return $this;
    }
    
    public function inQuery($field, array $values = array()) {
        foreach ($values as $eachValue) {
            $this->query($field, $eachValue);
        }
        return $this;
    }
    
    public function parseQuery() {
        return $this->query;
    }
    
    public function formatDateTime($date) {
        if (ctype_digit((string)$date)) {
            return date('Y-m-d\TH:i:s\Z', $date);
        }
        return date('Y-m-d\TH:i:s\Z', strtotime($date));
    }


    public function update() {
        
    }
    
    public function search(array $options = array()) {
        $q = self::DEFAULT_QUERY;
        $waitSuccess = isset($options['waitSuccess']) ? true : false;
        $data = $this->request($this->buildUrl($q, $options), $waitSuccess);
        return json_decode($data);  
    }        
    
    private function buildUrl($q = '', $options = array()) {
        $params = array(
            'omitHeader' => 'true',
            'q' => strlen($q) == 0 ? self::DEFAULT_QUERY : $q,
            'wt' => self::DEFAULT_WT,            
            'rows' => isset($options['rows']) && ctype_digit((string)$options['rows']) ? $this->validRows($options['rows']) : self::DEFAULT_ROWS,
            'start' => isset($options['start']) && ctype_digit((string)$options['start']) ? $options['start'] : self::DEFAULT_START,
        );
        if (isset($options['sort'])) $params['sort'] = $options['sort']; 
        return "{$this->slaveCore()}?" . http_build_query($params);
    }


    private function validRows($rows) {
        return $rows <= self::MAX_ROWS ? $rows : self::MAX_ROWS;
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
