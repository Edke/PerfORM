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
 * DibiOrm
 *
 * Base model's class responsible for model definition and interaction
 *
 * @abstract
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

abstract class DibiOrm
{


    /**
     * Table alias
     * @var string
     */
    protected $alias;


    /**
     * Default primary key field name used when autocreating
     * @var string
     */
    protected $defaultPrimaryKey= 'id';


    /**
     * Array of models that this model depends on
     * @var array
     */
    protected $depends= array();


    /**
     * Storage for model fields
     * @var array
     */
    protected $fields= array();


    /**
     * Switch for notifying if model (and which fields) was/were modified
     * @var boolean
     */
    protected $modified= false;


    /**
     * Name of primary key field
     * @var string
     */
    protected $primaryKey= null;


    /**
     * Sql name of model and table
     * @var string
     */
    protected $tableName= null;

    
    /**
     * Constructor
     *
     * Define model (build or load from cache) and import values if set
     *
     * @param array $importValues
     */
    public function  __construct($importValues = null)
    {
	if ( DibiOrmController::useModelCaching() )
	{
	    $this->loadDefinition();
	}
	else
	{
	    $this->buildDefinition();
	}

	if ( !is_null($importValues))
	{
	    $this->import($importValues);
	}
    }


    /**
     * Builds recursively aliases for $model
     * @param DibiOrm $model
     */
    protected function buildAliases($model, $aliases)
    {
	foreach($model->getFields() as $field)
	{
	    if ( get_class($field) == 'ForeignKeyField') {
		$foreignKeyTableName= $field->getReference()->getTableName();

		if ( key_exists($foreignKeyTableName, $aliases))
		{
		    $field->getReference()->setAlias($foreignKeyTableName.$aliases[$foreignKeyTableName]);
		    $aliases[$foreignKeyTableName]++;
		}
		else
		{
		    $field->getReference()->setAlias($foreignKeyTableName);
		    $aliases[$foreignKeyTableName]= 2;
		}
		$this->buildAliases($field->getReference(), $aliases);
	    }
	}
    }


    /**
     * Build model definition from setup
     *
     * Model will be cached if caching is enabled (DibiOrmController::useModelCaching())
     */
    protected function buildDefinition()
    {
	if ( is_null($this->getTableName()))
	{
	    $this->tableName= strtolower(get_class($this));
	}

	$this->setup();

	if ( !$this->getPrimaryKey())
	{
	    $this->fields= array($this->defaultPrimaryKey => new AutoField('primary_key=true')) + $this->fields; //unshift primary to beginning
	    $this->fields[$this->defaultPrimaryKey]->setName($this->defaultPrimaryKey);
	    $this->setPrimaryKey($this->defaultPrimaryKey);
	}

	$this->validate();

	# aliases for model
	$tableName= $this->getTableName();
	$aliases= array();
	$aliases[$tableName]= 2;
	$this->setAlias($tableName);
	$this->buildAliases($this, $aliases);

	if (DibiOrmController::useModelCaching())
	{
	    $cache= DibiOrmController::getCache();
	    $cache[$this->getCacheKey()]= $this;
	}
    }


    /**
     * Checks if $model depends on this model
     * @param DibiOrm $model
     * @return boolean
     */
    public function dependsOn($model)
    {
	foreach($this->depends as $dependent)
	{
	    if ( $model == $dependent )
	    {
		return true;
	    }
	}
	return false;
    }


    /**
     * Getter for alias
     * @return string
     */
    public function getAlias()
    {
	return $this->alias;
    }


    /**
     * Getter for model's cache key
     * @return string
     */
    protected function getCacheKey()
    {
	$mtime= DibiOrmController::getModelMtime($this);
	return md5($mtime.'|'.get_class($this));
    }


    /**
     * Getter for DibiOrmController's connection
     * @return DibiConnection
     */
    public function getConnection()
    {
	return DibiOrmController::getConnection();
    }


    /**
     * Getter for all dependents of model
     * @return array
     */
    public function getDependents()
    {
	return $this->depends;
    }


    /**
     * Getter for field with name $name
     * @return Field
     */
    public function getField($name)
    {
	if ( !key_exists($name, $this->fields))
	{
	    throw new Exception("field '$name' does not exists");
	}
	return $this->fields[$name];
    }


    /**
     * Getter for model's fields
     * @return array
     */
    public function getFields()
    {
	return $this->fields;
    }


    /**
     * Getter for foreign keys
     * @return array
     */
    public function getForeignKeys()
    {
	$keys= array();

	foreach($this->fields as $field)
	{
	    if ( get_class($field) == 'ForeignKeyField' )
	    {
		$keys[]= $field;
	    }
	}
	return $keys;
    }


    /**
     * Getter for DibiOrmController's driver
     * @return DibiOrmDriver
     */
    public function getDriver()
    {
	return DibiOrmController::getDriver();
    }


    /**
     * Getter for primary key field name
     * @return string
     * @todo remove check for multiple primary keys on table, needed to check elsewhere
     */
    public function getPrimaryKey()
    {

	if ( is_null($this->primaryKey))
	{
	    $primaryKey= null;
	    $hits= 0;
	    foreach($this->fields as $field)
	    {
		if ( $field->isPrimaryKey())
		{
		    $primaryKey= $field->getName();
		    $hits++;
		}
	    }

	    if ( $hits > 1 )
	    {
		throw new Exception("multiple primary keys on table '$this->getTableName()'");
	    }
	    elseif ( $hits > 0 )
	    {
		$this->setPrimaryKey($primaryKey);
		return $primaryKey;
	    }
	    else
	    {
		return false;
	    }
	}
	else
	{
	    return $this->primaryKey;
	}
    }


    /**
     * Getter of all model's properties
     *
     * Required for loading of serialized object's properties from cache
     */
    public function getProperties()
    {
	return get_object_vars($this);
    }


    /**
     * Getter for sql table name
     * @return string
     */
    public function getTableName()
    {
	return $this->tableName;
    }


    /**
     * Checks if model has field with name $name
     * @return boolean
     */
    public function hasField($name)
    {

	foreach($this->getFields() as $field)
	{
	    if ( $field->getRealName() == $name )
	    {
		return true;
	    }
	}
	return false;
    }


    /**
     * Import (load) model with values
     * @param array $values
     */
    public function import($values)
    {
	if ( !is_array($values))
	{
	    throw new Exception("invalid datatype of import values, array expected");
	}

	foreach($values as $field => $value)
	{
	    $this->{$field}= $value;
	}
    }


    /**
     * Add (insert) model to database
     *
     * Triggers NOTICE when no data to add
     *
     * @return mixed model's primary key value
     */
    public function insert()
    {
	$insert= array();

	foreach($this->fields as $key => $field)
	{
	    $finalColumn= $field->getRealName().'%'.$field->getType();

	    if ( !is_null($value = $field->getDbValue()) )
	    {
		$insert[$finalColumn]= $value;
	    }
	    elseif( !is_null($default = $field->getDefaultValue())  )
	    {
		$insert[$finalColumn]= $default;
	    }
	    elseif( $field->isNotNull() )
	    {
		throw new Exception("field '$key' has no value set or default value but not null");
	    }
	}

	if (count($insert)>0)
	{
	    //Debug::consoleDump($insert, 'insert array');

	    DibiOrmController::queryAndLog('insert into %n', $this->getTableName(), $insert);
	    $this->setUnmodified();
	    $insertId= $this->getConnection()->insertId();
	    $this->fields[$this->getPrimaryKey()]->setValue($insertId);
	    return $insertId;
	}
	else
	{
	    trigger_error("The model '".get_class($this)."' has no data to insert", E_USER_NOTICE);
	}
    }


    /**
     * Checks if model and it's fields are modified
     * @return boolean
     */
    public function isModified()
    {
	return $this->modified;
    }


    /**
     * Load model definition from cache if exists; if not, build model
     */
    protected function loadDefinition()
    {
	$cache= DibiOrmController::getCache();
	$cacheKey= $this->getCacheKey();
	if ( isset($cache[$cacheKey]) and is_object($cache[$cacheKey]) and get_class($cache[$cacheKey]) == get_class($this))
	{
	    foreach( $cache[$cacheKey]->getProperties() as $property => $value)
	    {
		$this->{$property}= $value;
	    }
	}
	else
	{
	    $this->buildDefinition();
	}
    }


    /**
     * Interface to QuerySet's
     * @return QuerySet
     */
    public function objects()
    {
	return new QuerySet($this);
    }


    /**
     * Saving model
     *
     * When primary key is set, model will be updated otherwise inserted
     * @return mixed model's primary key value
     */
    public function save()
    {
	$pk= $this->getPrimaryKey();
	if ( $this->fields[$pk]->getValue() )
	{
	    return $this->update();
	}
	else
	{
	    return $this->insert();
	}
    }


    /**
     * Setter for model alias
     * @param string $alias
     */
    public function setAlias($alias)
    {
	$this->alias= $alias;
    }

    
    /**
     * Setter for primary key field name
     * @param string $primaryKey
     */
    protected function setPrimaryKey($primaryKey)
    {
	$this->primaryKey= $primaryKey;
    }


    /**
     * Definition of model
     * @abstract
     */
    abstract protected function setup();


    /**
     * Set model and all it's fields to unmodified
     */
    protected function setUnmodified()
    {
	$this->modified= false;
	foreach( $this->getFields() as $field)
	{
	    $field->setUnmodified();
	}
    }


    /**
     * Save (update) model to database
     *
     * Only modified fields will be updated
     * Triggers NOTICE when no need to update
     *
     * @return mixed model's primary key value
     */
    public function update()
    {
	$update= array();

	foreach($this->fields as $key => $field)
	{
	    $finalColumn= $field->getRealName().'%'.$field->getType();

	    if ($field->isPrimaryKey())
	    {
		$primaryKey= $field->getRealName();
		$primaryKeyValue= $field->getDbValue();
		$primaryKeyType= $field->getType();
	    }
	    elseif ( !is_null($value = $field->getValue()) && $field->isModified() )
	    {
		$update[$finalColumn]= $value;
	    }
	    elseif( $field->isNotNull() )
	    {
		throw new Exception("field '$key' has no value set but not null");
	    }
	}

	if (count($update)>0)
	{
	    Debug::consoleDump($update, 'update array');
	    DibiOrmController::queryAndLog('update %n set', $this->getTableName(), $update, "where %n = %$primaryKeyType", $primaryKey, $primaryKeyValue);
	    $this->setUnmodified();
	    return $primaryKeyValue;
	}
	else
	{
	    trigger_error("The model '".get_class($this)."' has no unmodified data to update", E_USER_NOTICE);
	}
    }


    /**
     * Validate model's definition
     *
     * Throws Exception with all validation errors
     */
    protected function validate()
    {
	$errors= array();
	foreach($this->getFields() as $field)
	{
	    $errors= array_merge($errors, $field->validate());
	}
	if (count($errors)>0)
	{
	    throw new Exception(implode("; ", $errors));
	}
    }


    /**
     * Magic method for creating fields and setting it's values
     * @param string $field
     * @param mixed $value
     */
    public function __set($field,  $value)
    {
	// setting value for existing field
	if ( key_exists($field, $this->fields) && !is_object($value) )
	{
	    $this->fields[$field]->setValue($value);
	    $this->modified= true;
	}
	// setting new field
	elseif ( !key_exists($field, $this->fields) && is_object($value) )
	{
	    $this->fields[$field]= $value;
	    $this->fields[$field]->setName($field);

	    if (get_class($value) == 'ForeignKeyField')
	    {
		$this->depends[]= $value->getReference();
	    }

	}
	elseif ( key_exists($field, $this->fields) && is_object($value) )
	{
	    if ($this->fields[$field]->isForeignKey() && (get_class($value) == get_class($this->fields[$field]->getReference())) )
	    {
		$this->fields[$field]->setValue($value);
		$this->modified= true;
	    }
	    else
	    {
		Debug::consoleDump(array($field, $value), 'invalid setting on orm object');
		throw new Exception("column '$field' already exists");
	    }
	}
	else
	{
	    Debug::consoleDump(array($field, $value), 'invalid setting on orm object');
	    throw new Exception('invalid bigtime');
	}
    }


    /**
     * Magic method for getting field's values
     * @param string $field
     * @return mixed
     */
    public function  __get($field)
    {
	if ( key_exists($field, $this->fields) && is_object($this->fields[$field]))
	{
	    return $this->fields[$field]->getValue();
	}
	throw new Exception("invalid field name '$field'");
    }
}
