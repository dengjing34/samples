<?php

/**
 * Description of User
 *
 * @author jingd <jingd3@jumei.com>
 */
class Books extends Data {

    public $id, $name;

    function __construct() {
        $options = array(
            'key' => 'id',
            'table' => 'books',
            'columns' => array(
                'id' => 'id',
                'name' => 'name',
            ),
            'saveNeeds' => array(
            )
        );
        parent::init($options);
    }

}

?>
