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
 * PerfORM
 *
 * Base model's class responsible for model definition and interaction
 *
 * @abstract
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

abstract class PerfORM
{


    const AutoField = 3;
    const BooleanField = 5;
    const CharField = 2;
    const DateField = 10;
    const DateTimeField = 9;
    const DecimalField = 8;
    const ForeignKeyField = 4;
    const IntegerField = 1;
    const SmallIntegerField = 7;
    const TextField = 6;
    const TimeField = 11;


    /**
     * Table alias
     * @var string
     */
    protected $alias;


    /**
     * Alias index array to help to build aliases for object
     * @var array
     */
    protected $aliasIndex= array();


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
     * Storage for validation of model errors
     * @var array
     */
    protected $errors= array();


    /**
     * Storage for model fields
     * @var array
     */
    protected $fields= array();


    /**
     * Array of model's indexes
     * @var array
     */
    protected $indexes= array();


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
     * Suffix for table
     * @var string
     */
    protected $suffix= null;


    /**
     * Sql name of model and table
     * @var string
     */
    protected $tableName= null;


    /**
     * Hash of model for structure checking
     * @var string
     */
    protected $hash;

    
    /**
     * Constructor
     *
     * Define model (build or load from cache) and import values if set
     *
     * @param array $importValues
     */
    public function  __construct($importValues = null)
    {
	if ( PerfORMController::useModelCaching() )
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
     * Adds error message while validating model
     * @param string $msg
     */
    public function addError($msg)
    {
	$this->errors[]= str_replace('%s', get_class($this), $msg);
    }


    /**
     * Adds index to model
     * @param mixed $fieldNames
     * @param string $indexName
     * @param boolean $unique
     */
    public function addIndex($fieldNames, $indexName, $unique)
    {
	$suffix= ($unique) ? '_key' : '_idx';
	if ( !is_array($fieldNames))
	{
	    $fieldNames= array($fieldNames);
	}
	$key= is_null($indexName) ? implode('_',$fieldNames).$suffix : $indexName.$suffix;
	foreach($fieldNames as $fieldName)
	{
	    if ( !$this->hasField($fieldName))
	    {
		$this->addError(sprintf("%%s::%s (Index) field '%s' does not exists in model",$key, $fieldName));
	    }

	    if ( key_exists($key, $this->indexes))
	    {
		$this->indexes[$key]->addField($this->getField($fieldName)->getRealName());
	    }
	    else {
		$this->indexes[$key]= new Index($this, $this->getField($fieldName)->getRealName(), $key, $unique);
	    }
	}
    }


    /**
     * Builds recursively aliases for $model
     * @param PerfORM $model
     */
    protected function buildAliases($model)
    {
	foreach($model->getFields() as $field)
	{
	    if ( $field->getIdent() == PerfORM::ForeignKeyField) {
		$field->getReference()->setAlias($field->getReference()->getTableName());
		$this->buildAliases($field->getReference());
	    }
	}
    }


    /**
     * Build model definition from setup
     *
     * Model will be cached if caching is enabled (PerfORMController::useModelCaching())
     */
    protected function buildDefinition()
    {

	$this->setup();

	if ( !$this->getPrimaryKey())
	{
	    $this->fields= array($this->defaultPrimaryKey => new AutoField($this, 'primary_key=true')) + $this->fields; //unshift primary to beginning
	    $this->fields[$this->defaultPrimaryKey]->setName($this->defaultPrimaryKey);
	    $this->setPrimaryKey($this->defaultPrimaryKey);
	}

	$this->validate();

	# indexes
	foreach( $this->getFields() as $field)
	{
	    foreach($field->getIndexes() as $index)
	    {
		$this->addIndex($field->getName(), $index->name, $index->unique);
	    }
	}

	# aliases for model
	$this->setAlias($this->getTableName());
	$this->buildAliases($this);

	# model hashing
	$model_hashes= array();
	foreach( $this->getFields() as $field)
	{
	    $model_hashes[]= md5($field->getName().'|'.$field->getHash());
	}
	foreach( $this->getIndexes() as $index)
	{
	    $model_hashes[]= md5($index->getName().'|'.$index->getHash());
	}
	sort($model_hashes);
	$this->hash= md5(implode('|', $model_hashes));

	if (PerfORMController::useModelCaching())
	{
	    $cache= PerfORMController::getCache();
	    $cache[$this->getCacheKey()]= $this;
	}
    }


    /**
     * Checks if $model depends on this model
     * @param PerfORM $model
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
	return md5($this->getLastModification() .'|' .get_class($this));
    }


    /**
     * Getter for PerfORMController's connection
     * @return DibiConnection
     */
    public function getConnection()
    {
	return PerfORMController::getConnection();
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
     * Getter for model's hash
     * @return string
     */
    public function getHash()
    {
	return $this->hash;
    }


    /**
     * Getter for model's indexes
     * @return array
     */
    public function getIndexes()
    {
	return $this->indexes;
    }


    /**
     * Returns last modification of model
     * @return integer
     */
    abstract protected function getLastModification();


    /**
     * Getter for model's name
     */
    public function getName()
    {
	return get_class($this);
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
	if ( is_null($this->tableName))
	{
	    $this->tableName= strtolower(get_class($this));
	}
	return is_null($this->suffix) ? $this->tableName : $this->suffix . $this->tableName ;
    }


    /**
     * Checks if model has field with name $name
     * @return boolean
     */
    public function hasField($name)
    {

	foreach($this->getFields() as $field)
	{
	    if ( $field->getRealName() == $name or $field->getName() == $name )
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

	foreach($this->getFields() as $key => $field)
	{
	    $finalColumn= $field->getRealName().'%'.$field->getType();

	    if ($field->isPrimaryKey() and $field->getIdent() == PerfORM::AutoField)
	    {
	    }
	    elseif ( !is_null($value = $field->getDbValue(true)) )
	    {
		$insert[$finalColumn]= $value;
	    }
	    elseif( !$field->isNullable() )
	    {
		throw new Exception("field '$key' has no value set or default value but not null");
	    }
	}

	if (count($insert)>0)
	{
	    //Debug::consoleDump($insert, 'insert array');

	    PerfORMController::queryAndLog('insert into %n', $this->getTableName(), $insert);
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
	$cache= PerfORMController::getCache();
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
	if ( key_exists($alias, $this->aliasIndex))
	{
	    $this->aliasIndex[$alias]++;
	    $aliasIndex= $this->aliasIndex[$alias];
	}
	else
	{
	    $this->aliasIndex[$alias]= 1;
	    $aliasIndex= '';
	}
	$this->alias= $alias.$aliasIndex;
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
		$primaryKeyValue= $field->getDbValue(false);
		$primaryKeyType= $field->getType();
	    }
	    elseif ( !is_null($dbValue = $field->getDbValue(false)) && $field->isModified() )
	    {
		$update[$finalColumn]= $dbValue;
	    }
	    # if field has nullCallback defined, include it in update no matter if modified or not
	    elseif ( $field->getNullCallback() )
	    {
		$update[$finalColumn]= $dbValue;
	    }
	}

	if (count($update)>0)
	{
	    #Debug::consoleDump($update, 'update array');
	    PerfORMController::queryAndLog('update %n set', $this->getTableName(), $update, "where %n = %$primaryKeyType", $primaryKey, $primaryKeyValue);
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
	foreach($this->getFields() as $field)
	{
	    $this->errors= array_merge($this->errors, $field->validate());
	}
	if (count($this->errors)>0)
	{
	    throw new Exception(implode("; ", $this->errors));
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
	if( key_exists($field, $this->fields) &&
	    is_object($this->fields[$field]) &&
	    ($this->fields[$field]->getIdent() == PerfORM::DateTimeField ||
	    $this->fields[$field]->getIdent() == PerfORM::TimeField ||
	    $this->fields[$field]->getIdent() == PerfORM::DateField)
	    )
	{
	    return $this->fields[$field];
	}
	elseif ( key_exists($field, $this->fields) && is_object($this->fields[$field]))
	{
	    return $this->fields[$field]->getValue();
	}
	throw new Exception("invalid field name '$field'");
    }


    public function  __destruct()
    {
	unset($this->fields);
    }

}
