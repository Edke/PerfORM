<?php

/**
 * DibiOrm - Object-relational mapping based on David Grudl's dibi
 *
 * @copyright  Copyright (c) 2010 Eduard 'edke' Kracmar
 * @license    no license set at this point
 * @link       http://dibiorm.local :-)
 * @category   DibiOrm
 * @package    DibiOrm
 */


/**
 * Field, base model's field class
 *
 * @abstract
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
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
     * Hash of field for structure checking
     * @var string
     */
    protected $hash;


    /**
     * Is field mandatory ?
     * @var boolean
     */
    protected $isNullable = null;


    /**
     * Back reference to field's model
     * @var DibiOrm
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
    public function __construct($_options)
    {
	if (!is_object($_options[0]) and !is_subclass_of($_options[0], 'DibiOrm'))
	{
	    throw new Exception('First parameter of Field has to be parent Model');
	}
	$this->setModel($_options[0]);
	unset($_options[0]);

	$options= new Set();
	$options->import($_options);

	foreach ( $options as $option)
	{
	    if (is_object($option))
	    {
	    }
	    elseif ( strtolower($option) == 'null' )
	    {
		$this->setNullable();
		$options->remove($option);
	    }
	    elseif ( strtolower($option) == 'notnull' )
	    {
		$this->setNotNullable();
		$options->remove($option);
	    }
	    elseif ( preg_match('#^default=(.+)$#i', $option, $matches) )
	    {
		$this->setDefault( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( preg_match('#^default_callback=(.+)$#i', $option, $matches) )
	    {
		$this->setDefaultCallback( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( preg_match('#^recast_callback=(.+)$#i', $option, $matches) )
	    {
		$this->setRecastCallback( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( preg_match('#^db_column=(.+)$#i', $option, $matches) )
	    {
		$this->setDbName( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( strtolower($option) == 'primary_key=true' )
	    {
		$this->setPrimaryKey();
		$options->remove($option);
	    }
	}
	return $options;
    }


    /**
     * Adds error message while validating field's options
     * @param string $msg
     */
    protected function addError($msg)
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
     * Getter for field's value
     * @return mixed
     */
    public function getDbValue()
    {
	return $this->getValue();
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
     * Getter for field's callback that sets default value
     * @return callback
     */
    public  function getRecastCallback()
    {
	return $this->recastCallback;
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


    /**
     * Getter for field's model
     * @return DibiOrm
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
	/*if ( $this->dbName ) {
	    return $this->dbName;
	}*/
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
     * Determined whether field is a primary key
     * @return boolean
     */
    public function isPrimaryKey()
    {
	return $this->primaryKey;
    }


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


    /**
     * Sets field as required, not null
     */
    protected function setNotNullable()
    {
	if( !is_null($this->isNullable) )
	{
	    $this->addError("has already null/notnull");
	    return false;
	}
	$this->isNullable= false;
    }


    /**
     * Sets field as mandatory, is null
     */
    protected function setNullable()
    {
	if( !is_null($this->isNullable) )
	{
	    $this->addError("has already null/notnull");
	    return false;
	}
	$this->isNullable= true;
    }


    /**
     * Sets field's default value
     * @param miexd $default
     */
    final public function setDefault($default)
    {
	if( !is_null($this->default) )
	{
	    $this->addError("has already default value '$this->default'");
	    return false;
	}

	$retypeDefault= $this->retype($default);
	if ( (string) $default != (string) $retypeDefault )
	{
	    $this->addError("invalid datatype of default value '$default'");
	    return false;
	}

	$this->default= $retypeDefault;
    }


    /**
     * Sets field's callback for setting default value
     * @param array $callback
     */
    final public function setDefaultCallback($callback)
    {
	if( !is_callable(array($this->getModel(), $callback)) )
	{
	    $this->addError("callback '$callback' not callable on field's model");
	    return false;
	}
	$this->defaultCallback= array($this->getModel(), $callback);
    }


    /**
     * Sets name for field to be used in sql table
     * @param string $dbName
     */
    protected function setDbName($dbName)
    {
	if( !is_null($this->dbName) )
	{
	    $this->addError("has already set db_column '$this->dbName'");
	    return false;
	}
	$this->dbName= $dbName;
    }


    /**
     * Sets parent model
     * @param DibiOrm $model
     */
    protected function setModel($model)
    {
	$this->model= $model;
    }


    /**
     * Sets field's name
     * @param string $name
     */
    public function setName($name)
    {
	$this->name= $name;
    }


    /**
     * Sets field's callback for recasting datatype
     * @param array $callback
     */
    final public function setRecastCallback($callback)
    {
	if( !is_callable(array($this->getModel(), $callback)) )
	{
	    $this->addError("callback '$callback' not callable on field's model");
	    return false;
	}
	$this->recastCallback= array($this->getModel(), $callback);
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
     * Checks field for validation errors, inserts field name in message
     * @return array
     */
    public function validate()
    {
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
	unset($this->parent);
    }
}
