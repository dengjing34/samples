<?php
/**
 * Description of Video
 *
 * @author jingd <jingd3@jumei.com>
 */
class Category extends MysqlData {
    public $id, $pid, $cname, $status, $mid, $oid;
    const STATUS_ACTIVE = 1, LEVEL_ROOT = 0;
    public static $_status = array(
        self::STATUS_ACTIVE => '有效',
    );
    function __construct() {
        $options = array(
            'key' => 'id',
            'table' => 'gx_channel',
            'columns' => array(
                'id' => 'id',
                'pid' => 'pid',
                'cname' => 'cname',
                'status' => 'status',
                'mid' => 'mid',
                'oid' => 'oid',
            ),
            'saveNeeds' => array(
            ),
            'searcher' => null,//指定searcher的类名
        );
        parent::init($options);
    }
    
    public function find($options = array()) {
        $where = array(
            array('status', '=' . self::STATUS_ACTIVE),
            array('mid', '= 1'),
        );
        $order = array(
            'oid' => 'ASC',
        );
        $options['whereAnd'] = isset($options['whereAnd']) ? $options['whereAnd'] + $where : $where;
        $options['order'] = isset($options['order']) ? $options['order'] + $order : $order;        
        return parent::find($options);
    }
    
    public static function getCategories() {
        $o = new self();
        $categories = $o->find();        
        $result = array();
        /*@var $eachCategory Category */
        foreach ($categories as $eachCategory) {
            if ($eachCategory->pid == self::LEVEL_ROOT) {
                $result[$eachCategory->id] = $eachCategory;
                $result[$eachCategory->id]->children = array();
            }
        }        
        foreach ($result as $id => $eachFirst) {
            foreach ($categories as $eachCategory) {                
                if ($eachCategory->pid == $eachFirst->id) $result[$id]->children[$eachCategory->id] = $eachCategory;
            }
        }
        return $result;
    }
    
    public static function getSecondCategories() {
        $o = new self();
        return $o->find(array(
            'whereAnd' => array(
                array('pid', '<> ' . self::LEVEL_ROOT),
            ),
        ));
    }
    
    public static function letter() {
        return range('A', 'Z');
    }
    
    public static function year($limit = 20) {
        $current = date('Y');
        return range($current, $current - $limit);
    }
}
?>
