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
    protected $isNullable = null;

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


    /**
     * Hash of field for structure checking
     * @var string
     */
    protected $hash;


    protected $type;

    protected $value= null;

    protected $errors= array();

    protected $parent;

    public function __construct($_options)
    {
	if (!is_object($_options[0]) and !is_subclass_of($_options[0], 'DibiOrm'))
	{
	    throw new Exception('First parameter of Field has to be parent Model');
	}
	$this->setParent($_options[0]);
	unset($_options[0]);

	$options= new Set();
	$options->import($_options);

	foreach ( $options as $option){
	    if (is_object($option)) {
	    }
	    elseif ( strtolower($option) == 'null' ) {
		$this->setNullable();
		$options->remove($option);
	    }
	    elseif ( strtolower($option) == 'notnull' ) {
		$this->setNotNullable();
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

    protected function setNotNullable()
    {
	if( !is_null($this->isNullable) ) {
	    $this->addError("has already null/notnull");
	    return false;
	}
	$this->isNullable= false;
    }

    protected function setNullable()
    {
	if( !is_null($this->isNullable) ) {
	    $this->addError("has already null/notnull");
	    return false;
	}
	$this->isNullable= true;
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


    /**
     * Sets parent model
     * @param DibiOrm $parent
     */
    protected function setParent($parent)
    {
	$this->parent= $parent;
    }

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

    /**
     * Getter for fields model parent
     * @return DibiOrm
     */
    public function getParent()
    {
	return $this->parent;
    }


    public function getRealName()
    {
	/*if ( $this->dbName ) {
	    return $this->dbName;
	}*/
	return $this->name;
    }

    public  function getDefaultValue() {
	return $this->default;
    }


    /**
     * Getter for field's hash
     * @return string
     */
    public function getHash()
    {
	if ( !$this->hash )
	{
	    $array= array(
		$this->isPrimaryKey(),
		$this->isNullable(),
		$this->dbName,
		$this->getDefaultValue(),
		$this->getType()
	    );
	    $this->hash= md5(implode('|', $array));
	}
	return $this->hash;
    }


    public function isPrimaryKey() {
	return $this->primaryKey;
    }

    public function isForeignKey() {
	return false;
    }

    public function isNullable() {
	return $this->isNullable && true;
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

    public function getDbValue()
    {
	return $this->getValue();
    }


    public function  __destruct()
    {
	unset($this->parent);
    }
    
}

