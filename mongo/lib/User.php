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
            )
        );
        parent::init($options);
    }
}

?>
