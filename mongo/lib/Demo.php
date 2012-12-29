<?php
/**
 * Description of User
 *
 * @author jingd <jingd3@jumei.com>
 */
class Demo extends MongoData{
    public $name, $number, $age, $createdTime;
  
    function __construct() {
        $options = array(            
            'collection' => 'demo',
            'fields' => array(                
                'name',
                'number',
                'age',
                'createdTime',
            ),
            'fieldsAttributes' => array(
                'name' => array(
                    self::FIELD_REQUIRED => true,
                ),
                'age' => array(
                    self::FIELD_RULE => self::FIELD_RULE_NUM,
                ),
                'number' => array(
                    self::FIELD_RULE => self::FIELD_RULE_MOBILE,
                ),
            ),
        );
        parent::init($options);
    }
}

?>
