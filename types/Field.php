<?php

/**
 * Description of Field
 *
 * @author kraken
 */
abstract class Field {

    protected $name;

    /**
     * @var boolean
     */
    protected $primaryKey= false;

    /**
     * @var boolean
     */
    protected $isNull = null;

    /**
     * @var mixed
     */
    protected $default = null;

    /**
     * @var boolean
     */
    protected $modified= false;

    /**
     * @var string
     */
    protected $dbName= null;

    protected $type;

    protected $value= null;

    protected $errors= array();

    public function __construct($_options)
    {
	$options= new Set();
	$options->import($_options);

	foreach ( $options as $option){
	    if (is_object($option)) {
	    }
	    elseif ( strtolower($option) == 'null' ) {
		$this->setNull();
		$options->remove($option);
	    }
	    elseif ( strtolower($option) == 'notnull' ) {
		$this->setNotNull();
		$options->remove($option);
	    }
	    elseif ( preg_match('#^default=(.+)$#i', $option, $matches) ) {
		$this->setDefault( $matches[1]);
		$options->remove($option);
	    }
	    
	    elseif ( preg_match('#^db_column=(.+)$#i', $option, $matches) ) {
		$this->setDbName( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( strtolower($option) == 'primary_key=true' ) {
		$this->setPrimaryKey();
		$options->remove($option);
	    }
	}

	return $options;
    }

    protected function setNotNull()
    {
	if( !is_null($this->isNull) ) {
	    $this->addError("has already null/notnull");
	    return false;
	}
	$this->isNull= false;
    }

    protected function setNull()
    {
	if( !is_null($this->isNull) ) {
	    $this->addError("has already null/notnull");
	    return false;
	}
	$this->isNull= true;
    }

    final public function setDefault($default) {
	if( !is_null($this->default) ) {
	    $this->addError("has already default value '$this->default'");
	    return false;
	}

	$retypedDefault= $this->retyped($default);
	if ( (string) $default != (string) $retypedDefault ) {
	    $this->addError("invalid datatype of default value '$default'");
	    return false;
	}

	$this->default= $retypedDefault;
    }

    protected function setDbName($dbName) {
	if( !is_null($this->dbName) ) {
	    $this->addError("has already set db_column '$this->dbName'");
	    return false;
	}
	$this->dbName= $dbName;
    }

    public function setName($name) {
	$this->name= $name;
    }

    public function setValue($value) {
	$this->value= $this->retyped($value);
	$this->modified= true;
    }

    abstract function retyped($value);

    public function setPrimaryKey()
    {
	$this->primaryKey= true;
    }

    public function getValue()
    {
	return $this->value;
    }

    public function getName()
    {
	return $this->name;
    }

    public function getRealName()
    {
	if ( $this->dbName ) {
	    return $this->dbName;
	}
	return $this->name;
    }

    public  function getDefaultValue() {
	return $this->default;
    }

    public function isPrimaryKey() {
	return $this->primaryKey;
    }

    public function isForeignKey() {
	return false;
    }

    public function isNotNull() {
	return $this->isNull && false;
    }

    public function getType() {
	return $this->type;
    }

    public function validate() {
	foreach($this->errors as $key => $error) {
	    $this->errors[$key] = str_replace('%s', $this->name, $error);
	}
	return $this->errors;
    }
    
    protected function addError($msg) {
	if ( $this->name ) {
	    $this->errors[]= sprintf('%s (%s): %s', $this->name, get_class($this), $msg);
	}
	else {
	    $this->errors[]= '%s '. sprintf('(%s): %s',get_class($this), $msg);
	}
	
    }

     /**
      * @return boolean
      */
     public function isModified() {
	 return $this->modified;
     }

     public function setUnmodified()
     {
	 $this->modified= false;
     }

}

