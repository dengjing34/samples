<?php
/**
 * Description of Video
 *
 * @author jingd <jingd3@jumei.com>
 */
class video extends MysqlData {
    public $id, $title;
    function __construct() {
        $options = array(
            'key' => 'id',
            'table' => 'gx_video',
            'columns' => array(
                'id' => 'id',
                'title' => 'title',
            ),
            'saveNeeds' => array(
            )
        );
        parent::init($options);
    }
}
?>
