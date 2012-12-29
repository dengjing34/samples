<?php
/**
 * Description of User
 *
 * @author jingd <jingd3@jumei.com>
 */
class User extends MongoData{
    public $id, $name, $age, $gender, $status, $tag, $shape, $lang;
  
    function __construct() {
        $options = array(            
            'collection' => 'user',
            'fields' => array(
                'id',
                'name',
                'age',
                'gender',
                'status',
                'tag',
                'shape',
                'lang',
            ),
            'fieldsAttributes' => array(
                'name' => array(
                    self::FIELD_RULE => self::FIELD_RULE_ALPHANUM,
                ),
                'status' => array(
                    self::FIELD_REQUIRED => self::FIELD_RULE_NUM,
                ),
                'tag' => array(
                    
                ),
            ),
        );
        parent::init($options);
    }
}

?>
