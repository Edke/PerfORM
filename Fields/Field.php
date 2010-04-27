<?php

/**
 * PerfORM - Object-relational mapping based on David Grudl's dibi
 *
 * @copyright  Copyright (c) 2010 Eduard 'edke' Kracmar
 * @license    no license set at this point
 * @link       http://perform.local :-)
 * @category   PerfORM
 * @package    PerfORM
 */


/**
 * Field, base model's field class
 *
 * @abstract
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

abstract class Field
{


    /**
     * Name of field used for column it's sql table
     * @var string
     */
    protected $dbName= null;


    /**
     * Default value of field
     * @var mixed
     */
    protected $default = null;


    /**
     * Callback for setting default value
     * @var array
     */
    protected $defaultCallback = null;


    /**
     * Storage for validation errors
     * @var array
     */
    protected $errors= array();


    /**
     * Determined whether field was frozen
     * @var boolean
     */
    protected $freeze= false;

    /**
     * Hash of field for structure checking
     * @var string
     */
    protected $hash;


    /**
     * Array of indexes field is in
     * @var array
     */
    protected $indexes= array();


    /**
     * Is field mandatory ?
     * @var boolean
     */
    protected $isNullable = null;


    /**
     * Back reference to field's model
     * @var PerfORM
     */
    protected $model;


    /**
     * Was field's value modified (for update needs) ?
     * @var boolean
     */
    protected $modified= false;


    /**
     * Name of field
     * @var string
     */
    protected $name;


    /**
     * Is field a primary key ?
     * @var boolean
     */
    protected $primaryKey= false;


    /**
     * Callback called while recasting field
     * @var array
     */
    protected $recastCallback;


    /**
     * Callback called when object has null value (not default, this is used also while updating, mimics trigger behaviour)
     * @var array
     */
    protected $nullCallback;


    /**
     * Limit of field
     * @var integer
     */
    protected $size;


    /**
     * Datatype of field
     * @var string
     */
    protected $type;


    /**
     * Value of field
     * @var mixed
     */
    protected $value= null;


    /**
     * Constructor, parses for field's options and returns unknown ones
     *
     * @param array $_options
     * @return Set
     */
    public function  __construct($name)
    {
	if (empty($name))
	{
	    $this->_addError("Name was not set");
	}
	$this->setName($name);
    }


    /**
     * Adds error message while validating field's options
     * @param string $msg
     */
    protected function _addError($msg)
    {
	if ( $this->name )
	{
	    $this->errors[]= sprintf('%s (%s): %s', $this->name, get_class($this), $msg);
	}
	else
	{
	    $this->errors[]= '%s '. sprintf('(%s): %s',get_class($this), $msg);
	}
    }


    /**
     * Adds index
     * @param string $indexName
     * @param boolean $unique
     */
    public function addIndex($name = null, $unique = false)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	
	$suffix= ( $unique) ? 'key' : 'idx';
	$key = $name.'_'.$suffix;
	if ( key_exists($key, $this->indexes))
	{
	    $this->_addError("Index '$key' already exists");
	}
	else{
	    $this->indexes[$key]= (object) array(
		'name' => $name,
		'unique' => $unique,
	    );
	}
    }


    /**
     * Adds unique index
     * @param string
     */
    public function addUnique($name = null)
    {
	$this->addIndex($name, true);
    }


    /**
     * Freeze curent field's definition
     */
    public function freeze()
    {
	$this->freeze= true;
    }


    /**
     * Getter for field's value
     * @param boolean $insert true is insert, false is update
     * @return mixed
     */
    public function getDbValue($insert)
    {
	if ( $insert !== true and $insert !== false )
	{
	    throw new Exception("Mode is not set");
	}

	if ( !is_null($value = $this->getValue() ) ) {
	    return (string) $value;
	}
	elseif( !is_null($default = $this->getDefault()) && $insert )
	{
	    return $default;
	}
	elseif( !is_null($callback = $this->getNullCallback())  )
	{
	    return call_user_func($callback);
	}
	
	return null;
    }


    /**
     * Getter for field's default value or default_callback
     * @return mixed
     */
    public  function getDefault($row = null)
    {
	if( !is_null($default = $this->getDefaultValue())  )
	{
	    return $default;
	}
	elseif( $this->getDefaultCallback() )
	{
	    return call_user_func($this->getDefaultCallback(), $row);
	}
	else
	{
	    return null;
	}
    }


    /**
     * Getter for field's callback that sets default value
     * @return callback
     */
    public  function getDefaultCallback()
    {
	return $this->defaultCallback;
    }


    /**
     * Getter for field's default value
     * @return mixed
     */
    public  function getDefaultValue()
    {
	return $this->default;
    }


    /**
     * Getter for field's callback that sets default value
     * @return callback
     */
    public  function getRecastCallback()
    {
	return $this->recastCallback;
    }


    /**
     * Getter for field's Null callback
     * @return callback
     */
    public  function getNullCallback()
    {
	return $this->nullCallback;
    }


    /**
     * Returns identification integer of field
     */
    abstract public function getIdent();


    /**
     * Getter for field's indexes
     */
    public function getIndexes()
    {
	return $this->indexes;
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
	    $this->getType(),
	    get_class($this)
	    );
	    $this->hash= md5(implode('|', $array));
	}
	return $this->hash;
    }


    /**
     * Getter for field's model
     * @return PerfORM
     */
    public function getModel()
    {
	return $this->model;
    }


    /**
     * Getter for field's name
     * @return string
     */
    public function getName()
    {
	return $this->name;
    }


    /**
     * Getter for field's real name, considers it's db name, disabled temporary
     * @return string
     */
    public function getRealName()
    {
	if ( $this->dbName ) {
	    return $this->dbName;
	}
	return $this->name;
    }


    /**
     * Getter for size
     * @return boolean
     */
    public function getSize() {
	return false;
    }


    /**
     * Getter for field's type
     * @return string
     */
    public function getType()
    {
	return $this->type;
    }


    /**
     * Getter for field's value
     * @return mixed
     */
    public function getValue()
    {
	return $this->value;
    }


    /**
     * Determines whether field is foreign key
     * @return boolean
     */
    public function isForeignKey()
    {
	return false;
    }


    /**
     * Determines whether field was frozen
     * @return boolean
     */
    public function isFrozen()
    {
	return $this->freeze;
    }


    /**
     * Determined whether field is a primary key
     * @return boolean
     */
    public function isPrimaryKey()
    {
	return $this->primaryKey;
    }


    /**
     * Checks if value is valid for field
     * @param mixed $value
     * @return boolean
     */
    abstract protected function isValidValue($value);


    /**
     * Determines whether field is mandatory
     * @return <type>
     */
    public function isNullable()
    {
	return $this->isNullable && true;
    }


    /**
     * Determined whether field was modified
     * @return boolean
     */
    public function isModified()
    {
	return $this->modified;
    }


    /**
     * Retyping of field
     * @param mixed $value
     * @abstract
     */
    abstract function retype($value);


    protected function setName($name)
    {
	$this->name= strtolower($name);
    }


    /**
     * Sets field as required, not null
     * @return this
     */
    public function setNotNullable()
    {
	if ( $this->isFrozen()) throw new FreezeException();
	if( !is_null($this->isNullable) )
	{
	    $this->_addError("has already null/notnull");
	    return false;
	}
	$this->isNullable= false;
	return $this;
    }


    /**
     * Sets field as mandatory, is null
     * @return this
     */
    public function setNullable()
    {
	if ( $this->isFrozen()) throw new FreezeException();
	if( !is_null($this->isNullable) )
	{
	    $this->_addError("has already null/notnull");
	    return false;
	}
	$this->isNullable= true;
	return $this;
    }


    /**
     * Sets field's default value
     * @param miexd $default
     * @return this
     */
    public function setDefault($default)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	if( !is_null($this->default) )
	{
	    $this->_addError("has already default value '$this->default'");
	    return false;
	}
	$this->default= $default;
	return $this;
    }


    /**
     * Sets field's callback for setting default value
     * @param array $callback
     */
    public function setDefaultCallback($callback)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	if( !is_callable(array($this->getModel(), $callback)) )
	{
	    $this->_addError("callback '$callback' not callable on field's model");
	    return false;
	}
	$this->defaultCallback= array($this->getModel(), $callback);
	return $this;
    }


    /**
     * Sets name for field to be used in sql table
     * @param string $dbName
     */
    public function setDbName($dbName)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	trigger_error("Option db_column has been disabled as there is no support for advanced operations as rename column when it's set", E_USER_NOTICE);
	
	/*if( !is_null($this->dbName) )
	{
	    $this->_addError("has already set db_column '$this->dbName'");
	    return false;
	}
	$this->dbName= $dbName;*/
    }


    /**
     * Sets parent model
     * @param PerfORM $model
     */
    public function setModel($model)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	$this->model= $model;
    }


    /**
     * Sets field's callback for recasting datatype
     * @param array $callback
     * @return this
     */
    final public function setRecastCallback($callback)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	if( !is_callable(array($this->getModel(), $callback)) )
	{
	    $this->_addError("callback '$callback' not callable on field's model");
	    return false;
	}
	$this->recastCallback= array($this->getModel(), $callback);
	return $this;
    }


    /**
     * Sets field's onSave callback to get value that depends on other fields
     * @param array $callback
     */
    final public function setNullCallback($callback)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	if( !is_callable(array($this->getModel(), $callback)) )
	{
	    $this->_addError("callback '$callback' not callable on field's model");
	    return false;
	}
	$this->nullCallback= array($this->getModel(), $callback);
    }


    /**
     * Sets field's value
     * @param mixed $value
     */
    public function setValue($value)
    {
	$this->value= $this->retype($value);
	$this->modified= true;
    }


    /**
     * Sets field as primary key
     */
    public function setPrimaryKey()
    {
	if ( $this->isFrozen()) throw new FreezeException();
	$this->primaryKey= true;
    }


    /**
     * Resets field's modified status
     */
    public function setUnmodified()
    {
	$this->modified= false;
    }


    /**
     * Simplifies field
     * @param boolean $flat
     * @return mixed
     */
    public function simplify($flat)
    {
	return $this->getValue();
    }


    /**
     * Checks field for validation errors, inserts field name in message
     * @return array
     */
    public function validate()
    {
	# checking default value
	if ( $this->default &&
	    !$this->isValidValue($this->default))
	{
	    $this->_addError("invalid datatype of default value '$this->default'");
	}
	elseif ($this->default)
	{
	    $this->default= $this->retype($this->default);
	}

	foreach($this->errors as $key => $error)
	{
	    $this->errors[$key] = str_replace('%s', $this->getModel()->getName(). '::'.$this->name, $error);
	}
	return $this->errors;
    }


    /**
     * Cleans circular references, should prevent memory leaks
     */
    public function  __destruct()
    {
	unset($this->model);
    }
}
