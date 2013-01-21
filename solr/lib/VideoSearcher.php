<?php
/**
 * Description of VideoSearcher
 *
 * @author jingd <jingd3@jumei.com>
 */
class VideoSearcher extends Searcher{
    
    public function __construct() {
        $options = array(
            'core' => 'video',
        );
        parent::init($options);
    }
}

?>
