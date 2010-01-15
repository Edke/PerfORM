<?php
/**
 * my dibi orm attempt
 *
 * @author kraken
 */
class DibiOrm extends DibiOrmBase {

    /**
     * @var array
     */
    protected $fields= array();

    /**
     * @var string
     */
    protected $tableName= null;

    /**
     * @var string
     */
    protected $primaryKey= null;

    public function  __construct()
    {
	if ( is_null($this->getTableName())) {
	    $this->tableName= get_class($this);
	}

	$this->setup();

	if ( !$this->getPrimaryKey()) {
	    $this->fields= array('id' => new AutoField()) + $this->fields; //unshift primary to beginning
	    $this->fields['id']->setName('id');
	    $this->setPrimaryKey('id');
	}

	$this->validate();
    }

    public function  __set($field,  $value)
    {
	// setting value for existing field
	if ( key_exists($field, $this->fields) && !is_object($value) ) {
	    $this->fields[$field]->setValue($value);
	}
	// setting new field
	elseif ( !key_exists($field, $this->fields) && is_object($value) ) {
	    $this->fields[$field]= $value;
	    $this->fields[$field]->setName($field);
	}
	elseif ( key_exists($field, $this->fields) && is_object($value) ) {
	    Debug::consoleDump(array($field, $value), 'invalid setting on orm object');
	    throw new Exception("column '$field' already exists");
	}
	
	else{
	    Debug::consoleDump(array($field, $value), 'invalid setting on orm object');
	    throw new Exception('invalid bigtime');
	}
    }

    public function  __get($field)
    {
	if ( $this->fields->offsetExists($field) && is_object($this->fields[$field])) {
	    return $this->fields[$field]->getValue();
	}
	throw new Exception("invalid field name '$field'");
    }

    public function getPrimaryKey() {

	if ( is_null($this->primaryKey)) {
	    $primaryKey= null;
	    $hits= 0;
	    foreach($this->fields as $field)
	    {
		if ( $field->isPrimaryKey()) {
		    $primaryKey= $field->getName();
		    $hits++;
		}
	    }

	    if ( $hits > 1 ) {
		throw new Exception("multiple primary keys on table '$this->getTableName()'");
	    }
	    elseif ( $hits > 0 ) {
		$this->setPrimaryKey($primaryKey);
		return $primaryKey;
	    }
	    else {
		return false;
	    }
	}
	else {
	    return $this->primaryKey;
	}
    }

    protected function setPrimaryKey($primaryKey) {
	$this->primaryKey= $primaryKey;
    }

    public function getTableName()
    {
	if ( is_string($this->tableName)) {
	    return strtolower($this->tableName);
	}
	return $this->tableName;
    }
    
    public function save() {
	$pk= $this->getPrimaryKey();
	if ( $this->fields[$pk]->getValue() ) {
	    $this->update();
	}
	else {
	    $this->insert();
	}
    }


    public function insert() {
	$insert= array();

	foreach($this->fields as $key => $field) {

	    Debug::consoleDump($field);
	    $finalColumn= $field->getRealName().'%'.$field->getType();

	    if ( !is_null($value = $field->getValue()) ) {
		$insert[$finalColumn]= $value;
	    }
	    elseif( !is_null($default = $field->getDefaultValue())  ) {
		$insert[$finalColumn]= $default;
	    }
	    elseif( $field->isNotNull() ) {
		throw new Exception("field '$key' has no value set or default value but not null");
	    }
	}

	if (count($insert)>0) {
	    Debug::consoleDump($insert, 'insert array');
	    $this->getConnection()->query('insert into %n', $this->getTableName(), $insert);
	    return $this->getConnection()->insertId();
	}
	else {
	    throw new Exception('nothing to insert');
	}
    }

    /**
     * @return array
     */
    public function getFields(){
	return $this->fields;
    }

    /**
     * @return Field
     */
    public function getField($name){
	if ( !key_exists($name, $this->fields)) {
	    throw new Exception("field '$name' does not exists");
	}
	return $this->fields[$name];
    }

    /**
     * @return Field
     */
    public function hasField($name){

	foreach($this->getFields() as $field) {
	    if ( $field->getRealName() == $name ) {
		return true;
	    }
	}
	return false;
    }


    /**
     * validate fields definition
     */
    protected function validate() {
	$errors= array();
	foreach($this->getFields() as $field) {
	    $errors= array_merge($errors, $field->validate());
	}
	if (count($errors)>0) {
	    throw new Exception(implode("; ", $errors));
	}
    }
    
}
