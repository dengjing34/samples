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
            'fieldList' => array(
                'id',
                'title',
                'content',
                'actor',
                'director',
                'hits',
                'language',
                'year',
                'cid',
                'createdtime',
                'addtime',
                'letter',
                'rank',
                'status',
                'intro',
            ),
            'dbObject' => 'Video',
        );
        parent::init($options);
    }
}

?>
