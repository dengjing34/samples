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
    private static $host = array(), $query = array(), $cores = array(), $start = 0, $rows = 10;
    
    private function __construct() {
        ;
    }
    
    protected function init(array $options = array()) {
        $required = array_flip(array(
            'core',
        ));
        
    }


    private function request() {
        
    }       
    
    public function query() {
        
    }
    
    public function update() {
        
    }
    
    public function search() {
        
    }
    
    private function buildUrl() {
        
    }
    
    private function validRows() {
        return self::$rows <= self::MAX_ROWS ? self::$rows : self::MAX_ROWS;
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
    
    
}

?>
