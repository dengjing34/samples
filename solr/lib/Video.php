<?php
/**
 * Description of Video
 *
 * @author jingd <jingd3@jumei.com>
 */
class Video extends MysqlData {
    public $id, $title, $content, $actor, $director, $hits, $language, $year, $cid, $addtime;
    public $letter, $rank, $status, $intro, $attributeData;
    function __construct() {
        $options = array(
            'key' => 'id',
            'table' => 'gx_video',
            'columns' => array(
                'id' => 'id',
                'title' => 'title',
                'content' => 'content',
                'actor' => 'actor',
                'director' => 'director',
                'hits' => 'hits',
                'language' => 'language',
                'year' => 'year',
                'cid' => 'cid',                
                'addtime' => 'addtime',
                'letter' => 'letter',
                'rank' => 'score',
                'status' => 'status',
                'intro' => 'intro',
                'attributeData' => 'attributeData',
            ),
            'saveNeeds' => array(
            ),
            'searcher' => 'VideoSearcher',//指定searcher的类名
        );
        parent::init($options);
    }
    
    public function save() {
        $key = $this->key();
        if (is_null($this->{$key})) {
            $this->set('createdtime', date('Y-m-d\TH:i:s\Z'));
            $this->addtime = time();            
        } else {
            if (!$this->get('createdtime')) {
                $this->set('createdtime', date('Y-m-d\TH:i:s\Z'));
            }
        }        
        return parent::save();
    }
}
?>
