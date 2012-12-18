<?php

/**
 * Description of User
 *
 * @author jingd <jingd3@jumei.com>
 */
class UserBook extends Data {

    public $userId, $bookId;

    function __construct() {
        $options = array(
            'key' => 'userId',
            'table' => 'user_book',
            'columns' => array(
                'userId' => 'user_id',
                'bookId' => 'book_id',
            ),
            'saveNeeds' => array(
            )
        );
        parent::init($options);
    }

}

?>
