<?php

/**
 * Description of User
 *
 * @author jingd <jingd3@jumei.com>
 */
class User extends Data {

    public $id, $name;

    function __construct() {
        $options = array(
            'key' => 'id',
            'table' => 'user',
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
