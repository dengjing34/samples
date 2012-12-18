<?php
/**
 * Description of User
 *
 * @author jingd <jingd3@jumei.com>
 */
class Demo extends MongoData{
    public $name, $number, $createdTime;
  
    function __construct() {
        $options = array(            
            'collection' => 'demo',
            'fields' => array(                
                'name',
                'number',
                'createdTime',
            )
        );
        parent::init($options);
    }
}

?>
