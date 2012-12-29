<?php
/**
 *
 * @author jingd <jingd3@jumei.com>
 */
abstract class Data {
    /**
     * if set true, field is not null
     */
    const FIELD_REQUIRED = 'required';
    /**
     * field's regular expression, if this has been set, FIELD_REQUIRED will not affected
     */
    const FIELD_RULE = 'rule';
    const FIELD_RULE_NUM = 'num';
    const FIELD_RULE_EMAIL = 'email';
    const FIELD_RULE_LETTER = 'letter';
    const FIELD_RULE_MOBILE = 'mobile';
    const FIELD_RULE_ALPHANUM = 'alphanum';    
    /**
     * field name, for form or listing to display
     */
    const FIELD_NAME = 'name';
    /**
     * form input type for field
     */
    const FIELD_INPUT_TYPE = 'type';
    /**
     * form input size for field
     */
    const FIELD_INPUT_SIZE = 'size';
    /**
     * when FIELD_INPUT_TYPE = 'select', set default options for this select tag
     */
    const FIELD_INPUT_SELECT_OPTIONS = 'options';
    /**
     * if set it true, form input will add readonly="readonly" attribute
     */
    const FIELD_INPUT_READONLY = 'readonly';
    /**
     * width of this filed in a form
     */
    const FIELD_INPUT_WIDTH = 'width';
    /**
     * height of this filed in a form
     */
    const FIELD_INPUT_HEIGHT = 'height';
    /**
     * if set it true, form input will add disabled="disabled" attribute
     */
    const FIELD_INPUT_DISABLED = 'disabled';
    /**
     * some rich text editor's toolbar style, for ckeditor, can be ‘Basic|Full’
     */
    const FIELD_INPUT_TOOLBAR = 'toolbar';
    /**
     * if FIELD_INPUT_TYPE = 'file' and set this true, the image uploaded will be resized by config
     */
    const FIELD_INPUT_RESIZABLE = 'resizable';
    /**
     * if FIELD_INPUT_TYPE = 'file' and set this true, the image uploaded will be added a watermark by config
     */
    const FIELD_INPUT_WATERMARK = 'watermark';
    protected static $connections = array();
    protected static $fields = array(), $fieldsAttributes = array();
    protected static $fieldRules = array(
        self::FIELD_RULE_NUM => '/^\d+$/',
        self::FIELD_RULE_ALPHANUM => '/^[a-zA-Z0-9]+$/',
        self::FIELD_RULE_LETTER => '/^[a-zA-Z]+$/',
        self::FIELD_RULE_MOBILE => '/^1[358]\d{9}$/',
        self::FIELD_RULE_EMAIL => '/^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/',
    );

    /**
     * avoid to new base class directly such as MongoData, MysqlData etc.
     */
    private function __construct() {}
    
    /**
     * when clone clean $this
     */
    protected function __clone() {
        $this->clean();       
    }
    
    /**
     * clean object's properties by $fields
     * @return $this
     */
    protected function clean() {        
        foreach (self::$fields[get_parent_class($this)][get_class($this)] as $property => $field)
            $this->{$property} = null;
        return $this;
    }
    
    /**
     * validate data when save(), insert(), update()
     * @return \Data $this
     * @throws Exception
     */
    protected function validateFields() {        
        $errors = array();
        foreach ($this->getFieldsAttributes() as $field => $attr) {
            if (isset($attr[self::FIELD_REQUIRED]) && $attr[self::FIELD_REQUIRED] && strlen(trim($this->{$field})) == 0) {                
                $errors[] = "'{$field}' is required";
            }
            if (isset($attr[self::FIELD_RULE])) {
                if (isset(self::$fieldRules[$attr[self::FIELD_RULE]]) && !preg_match(self::$fieldRules[$attr[self::FIELD_RULE]], $this->{$field})) {
                    $errors[] = "'{$field}' must be " . $attr[self::FIELD_RULE] . ' format';
                } elseif (!isset(self::$fieldRules[$attr[self::FIELD_RULE]]) && !preg_match($attr[self::FIELD_RULE], $this->{$field})) {
                    $errors[] = "'{$field}' is invalid";
                }
            }
        }
        if (!empty($errors)) throw new Exception(implode(";\n", $errors));
        return $this;
    }
    
    /**
     * validate an array of data when use batchInsert() method 
     * @param array $data an array of data going to be inserted
     * @return \Data $this
     * @throws Exception
     */
    protected function validateFieldsArray(array $data = array()) {        
        $fieldsAttributes = $this->getFieldsAttributes();
        foreach ($data as $key => $eachData) {
            $errors = array();
            foreach ($fieldsAttributes as $field => $attr) {
                if (isset($attr[self::FIELD_REQUIRED]) && $attr[self::FIELD_REQUIRED]) {
                    if (!isset($eachData[$field])) {
                        $errors[] = "'{$field}' is required";
                    } elseif (isset($eachData[$field]) && strlen(trime($eachData[$field]))) {                        
                        $errors[] = "'{$field}' is required";
                    }
                }
                if (isset($attr[self::FIELD_RULE])) {
                    if (!isset($eachData[$field])) {
                        $errors[] = "'{$field}' is invalid";
                    } elseif (isset(self::$fieldRules[$attr[self::FIELD_RULE]]) && !preg_match(self::$fieldRules[$attr[self::FIELD_RULE]], $eachData[$field])) {
                        $errors[] = "'{$field}' must be " . $attr[self::FIELD_RULE] . ' format';
                    } elseif (!isset(self::$fieldRules[$attr[self::FIELD_RULE]]) && !preg_match($attr[self::FIELD_RULE], $eachData[$field])) {                        
                        $errors[] = "'{$field}' is invalid";
                    }                    
                }
            }
            if (!empty($errors)) throw new Exception("data[{$key}]:\n" . implode(";\n", $errors));
        }
        return $this;
    }


    /**
     * get instance's fields attributes
     * @return array 
     */
    protected function getFieldsAttributes() {
        return self::$fieldsAttributes[get_parent_class($this)][get_class($this)];
    }

    abstract protected function load($id) ;
    
    abstract protected function loadByIds(array $ids);
    
    abstract protected function find(array $query) ;
    
    abstract protected function insert(array $data);
    
    abstract protected function update(array $data);
    
    abstract protected function save(array $data);
    
    abstract protected function remove($id = null);
    
    abstract protected function count(array $query);
    
    abstract protected function getConnection($flag = null);        
}

?>
