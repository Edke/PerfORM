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

abstract class PerfORM extends Object
{

    const AutoField = 3;
    const BooleanField = 5;
    const CharField = 2;
    const DateField = 10;
    const DateTimeField = 9;
    const DecimalField = 8;
    const EmailField = 13;
    const ForeignKeyField = 4;
    const IntegerField = 1;
    const IPAddessField = 12;
    const SmallIntegerField = 7;
    const SlugField = 15;
    const TextField = 6;
    const TimeField = 11;
    const URLField = 14;


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
     * Instance of
     * @var PerfORM
     */
    protected $extends;


    /**
     * Storage for model fields
     * @var array
     */
    protected $fields= array();


    /**
     * Determines if model was frozen
     * @var boolean
     */
    protected $freeze= false;


    /**
     * Hash of model for structure checking
     * @var string
     */
    protected $hash;


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
     * Prefix for table
     * @var string
     */
    protected $prefix= null;


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
     * Determines if model is view or table, table is default
     * @var boolean
     */
    protected $view= false;

    
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
    public function _addError($msg)
    {
	$this->errors[]= str_replace('%s', get_class($this), $msg);
    }


    /**
     * Adds new AutoField to model
     * @param string $name
     * @return AutoField
     */
    protected function addAutoField($name)
    {
	return $this->attachField(new AutoField($name));
    }


    /**
     * Adds new BooleanField to model
     * @param string $name
     * @return BooleanField
     */
    protected function addBooleanField($name)
    {
	return $this->attachField(new BooleanField($name));
    }


    /**
     * Adds new CharField to model
     * @param string $name
     * @param integer $maxLength
     * @return CharField
     */
    protected function addCharField($name, $maxLength)
    {
	return $this->attachField(new CharField($name, $maxLength));
    }


    /**
     * Adds new DateField to model
     * @param string $name
     * @return DateField
     */
    protected function addDateField($name)
    {
	return $this->attachField(new DateField($name));
    }


    /**
     * Adds new DateTimeField to model
     * @param string $name
     * @return DateTimeField
     */
    protected function addDateTimeField($name)
    {
	return $this->attachField(new DateTimeField($name));
    }


    /**
     * Adds new DecimalField to model
     * @param string $name
     * @param integer $maxDigits
     * @param integer $decimalPlaces
     * @return DecimalField
     */
    protected function addDecimalField($name, $maxDigits, $decimalPlaces)
    {
	return $this->attachField(new DecimalField($name, $maxDigits, $decimalPlaces));
    }


    /**
     * Adds new EmailField to model
     * @param string $name
     * @param integer $maxLength
     * @return EmailField
     */
    protected function addEmailField($name, $maxLength)
    {
	return $this->attachField(new EmailField($name, $maxLength));
    }


    /**
     * Adds new ForeignKeyField to model
     * @param string $name
     * @param PerfORM $reference
     * @return ForeignKeyField
     */
    protected function addForeignKeyField($name, $reference)
    {
	$field= $this->attachField(new ForeignKeyField($name, $reference));
	$this->depends[]= $field->getReference();
	return $field;
    }


    /**
     * Adds new IPAddressField to model
     * @param string $name
     * @return IPAddressField
     */
    protected function addIPAddressField($name)
    {
	return $this->attachField(new IPAddressField($name));
    }


    /**
     * Adds new IntegerField to model
     * @param string $name
     * @return IntegerField
     */
    protected function addIntegerField($name)
    {
	return $this->attachField(new IntegerField($name));
    }


    /**
     * Adds new SlugField to model
     * @param string $name
     * @param integer $maxLength
     * @param string $autoSource
     * @return SlugField
     */
    protected function addSlugField($name, $maxLength, $autoSource)
    {
	return $this->attachField(new SlugField($name, $maxLength, $autoSource));
    }


    /**
     * Adds new SmallIntegerField to model
     * @param string $name
     * @return SmallIntegerField
     */
    protected function addSmallIntegerField($name)
    {
	return $this->attachField(new SmallIntegerField($name));
    }


    /**
     * Adds new TextField to model
     * @param string $name
     * @return TextField
     */
    protected function addTextField($name)
    {
	return $this->attachField(new TextField($name));
    }


    /**
     * Adds new TimeField to model
     * @param string $name
     * @return TimeField
     */
    protected function addTimeField($name)
    {
	return $this->attachField(new TimeField($name));
    }


    /**
     * Adds new URLField to model
     * @param string $name
     * @param integer $maxLength
     * @return URLField
     */
    protected function addURLField($name, $maxLength)
    {
	return $this->attachField(new URLField($name, $maxLength));
    }


    /**
     * Adds field to model
     * @param string $fieldName
     * @param Field $field
     */
    protected function attachField($field, $toBeginning= false)
    {
	if ( $this->isFrozen() )
	{
	    throw new Exception("Unable to attach field '".$field->getName()."' to frozen model.");
	}
	
	if ( key_exists($field->getName(), $this->fields ) )
	{
	    throw new Exception ("Field with name '".$field->getName()."' already exists in model '".get_class($this)."'");
	}

	if ( $toBeginning)
	{
	    $this->fields= array($field->getName() => $field) + $this->fields;
	}
	else {
	    $this->fields[$field->getName()]= $field;
	}
	$this->fields[$field->getName()]->setModel($this);
	return $field;
    }


    /**
     * Adds index to model
     * @param mixed $fieldNames
     * @param string $indexName
     * @param boolean $unique
     */
    protected function addIndex($fieldNames, $indexName, $unique)
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
		$this->_addError(sprintf("%%s::%s (Index) field '%s' does not exists in model",$key, $fieldName));
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
		$alias= $field->getReference()->getTableName();
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
		$field->getReference()->setAlias($alias.$aliasIndex);
		foreach( $field->getReference()->getFields() as $_field )
		{
		    $_field->getModel()->setAlias($alias.$aliasIndex);
		}
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

	if ( !$this->getPrimaryKey() && $this->isTable() && $this->isExtended() )
	{
	    $field= new IntegerField($this->defaultPrimaryKey);
	    $field->setPrimaryKey();
	    $this->attachField($field, true);
	    $this->setPrimaryKey($this->defaultPrimaryKey);
	}
	elseif ( !$this->getPrimaryKey() && $this->isTable() )
	{
	    $field= new AutoField($this->defaultPrimaryKey);
	    $field->setPrimaryKey();
	    $this->attachField($field, true);
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
	if ( $this->isTable())
	{
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
	}
	elseif ( $this->isView())
	{
	    $view= $this->getViewSetup();
	    $view= trim(preg_replace('#\s{2,}#m',' ', $view));
	    $this->hash= md5($view);
	}
	
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
     * Fill model with values from result
     * @param PerfORM $model
     * @param array $values
     * @result PerfORM
     */
    public function fill($values)
    {
	if ($values === false) return;

	foreach($this->getFields() as $field)
	{
	    $key= $this->getAlias().'__'.$field->getRealName();

	    if ( $field->getIdent() == PerfORM::ForeignKeyField &&
		!key_exists($field->getReference()->getAlias().'__'.$field->getReference()->getPrimaryKey(), $values))
	    {
		$field->setLazyLoadingKeyValue($values[$key]);
		$field->enableLazyLoading();

	    }
	    elseif ( $field->getIdent() == PerfORM::ForeignKeyField &&
		!$field->isEnabledLazyLoading()
	    )
	    {
		$child= clone $field->getReference();
		$child->fill($values);
		$field->setValue($child);
	    }
	    elseif ( $field->getIdent() == PerfORM::ForeignKeyField &&
		$field->isEnabledLazyLoading()
	    )
	    {
		$field->setLazyLoadingKeyValue($values[$key]);
	    }
	    elseif ( key_exists($key, $values))
	    {
		$this->__set($field->getName(), $values[$key]);
	    }
	    else
	    {
		throw new Exception("The is no value in result for field '$key'");
	    }
	}
	$this->setUnmodified();

	if ( $this->isExtended())
	{
	    $this->extends->fill($values);
	}
    }

    /**
     * Freezes model's definition after building
     */
    protected function freeze()
    {
	$this->freeze= true;
	foreach($this->getFields() as $field)
	{
	    $field->freeze();
	}
    }


    /**
     * Sets inheritance
     * @param PerfORM $model
     */
    protected function setInheritance($model)
    {
	$this->extends= $model;
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
     * Getter for parent model
     * @return PerfORM
     */
    public function getExtend()
    {
	return $this->extends;
    }


    /**
     * Getter for field with name $name
     * @return Field
     */
    public function getField($name, $includeExtends = false)
    {
	if ( key_exists($name, $this->fields))
	{
	    return $this->fields[$name];
	}
	elseif( $this->isExtended() and $includeExtends)
	{
	    $result= $this->extends->getField($name, true);
	    return $result;
	}
	else
	{
	    throw new Exception("field '$name' does not exists");
	}
    }


    /**
     * Getter for array of all fields of model (including inheritated fields)
     * @return array
     */
    public function getFieldNames()
    {
	$fieldNames= array();
	foreach($this->getFields() as $field)
	{
	    $fieldNames[$field->getName()]= $field->getName();
	}
	if ( $this->extends)
	{
	    $fieldNames= array_merge($fieldNames, $this->extends->getFieldNames());
	}
	return $fieldNames;
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
     * Getter for all tablenames from inheritated models
     * @return array
     */
    public function getAllTableNames()
    {
	$result= array();
	if ( $this->isExtended())
	{
	    $result= array_merge($result, $this->getExtend()->getAllTableNames());
	}
	$result= array_merge($result, array($this->getTableName()));
	return $result;
    }


    /**
     * Getter for sql table name
     * @return string
     */
    public function getTableName()
    {
	if ( is_null($this->tableName))
	{
	    $className= get_class($this);
	    $className[0] = strtolower($className[0]);
	    $func = create_function('$c', 'return "_" . strtolower($c[1]);');
	    $this->tableName= preg_replace_callback('/([A-Z])/', $func, $className);
	}
	return is_null($this->prefix) ? $this->tableName : $this->prefix . $this->tableName ;
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
	if ( $this->extends)
	{
	    return $this->extends->hasField($name);
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
		throw new Exception(get_class($this). " - field '$key' has no value set or default value but not null");
	    }
	}

	if (count($insert)>0)
	{
	    #Debug::barDump($insert, 'insert array');
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
     * Checks if model is extended
     * @return boolean
     */
    public function isExtended()
    {
	return isset($this->extends) ? true : false;
    }


    /**
     * Checks if model was frozen
     * @return boolean
     */
    protected function isFrozen()
    {
	return $this->freeze;
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
     * Determines if model is table
     * @return boolean
     */
    public function isTable()
    {
	return !$this->view;
    }


    /**
     * Determines if model is view
     * @return boolean
     */
    public function isView()
    {
	return $this->view;
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
     * Finder of reference field mapped with $path
     *
     * @example pneumatika__dezen__nazov
     * @param string $path
     * @param string $delimiter
     * @result Field
     */
    public function pathLookup($path, $delimiter = '__')
    {
	$pointer= null;
	$reference= $this;
	$fields= explode($delimiter, $path);
	$iterator= count($fields);
	foreach($fields as $field)
	{
	    $iterator--;
	    if ($reference->hasField($field) &&
		$reference->getField($field)->getIdent() == PerfORM::ForeignKeyField)
	    {
		$pointer= $reference->getField($field);
		$reference= $pointer->getReference();
	    }
	    elseif ( $reference->hasField($field) &&
		$iterator === 0 )
	    {
		$pointer= $reference->getField($field);
	    }
	    else {
		throw new Exception("Invalid element '$field' in path '$path'.");
	    }
	}
	return $pointer;
    }


    /**
     * Saving model
     *
     * When primary key is set, model will be updated otherwise inserted
     * @return mixed model's primary key value
     */
    public function save()
    {
	if ( $this->isView() )
	{
	    throw new Exception('Unable to save view.');
	}

	$pk= $this->getPrimaryKey();

	// has primary key set, record exists -> updating
	if ($this->fields[$pk]->getValue())
	{
	    if ($this->isExtended())
	    {
		$this->extends->save();
	    }
	    return $this->update();
	}
	// no primary key value, record does not exists -> inserting
	else
	{
	    if ($this->isExtended())
	    {
		$this->{$pk}= $this->extends->save();
	    }
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


    public function setLazyLoading()
    {
	$paths= func_get_args();
	if (empty($paths))
	{
	    foreach($this->getForeignKeys() as $field)
	    {
		$field->enableLazyLoading();
	    }
	    return;
	}

	foreach($paths as $path)
	{
	    $this->pathLookup($path, '->')->enableLazyLoading();
	}
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
    public function setUnmodified()
    {
	$this->modified= false;
	foreach( $this->getFields() as $field)
	{
	    $field->setUnmodified();
	}

	if ( $this->extends)
	{
	    $this->extends->setUnmodified();
	}
    }


    /**
     * Returns simplified version of model, stdClass filled with current values
     * @param boolean $flat
     * @return stdClass
     */
    public function simplify($flat = false)
    {
	$result= new stdClass;

	foreach($this->getFieldNames() as $fieldName)
	{
	    $result->{$fieldName} = $this->getField($fieldName, true)->simplify($flat);
	}
	return $result;
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
	    #Debug::barDump($update, 'update array');
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
	
	if ( $this->isView() && !(method_exists($this,'getViewSetup') and is_string($this->getViewSetup())) )
	{
	    $this->_addError('%s - invalid view definition');
	}

	if (count($this->errors)>0)
	{
	    throw new Exception(implode("; ", $this->errors));
	}
    }


    /**
     * Magic method for creating fields and setting it's values
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
	$name= strtolower($name);
	if ($this->hasField($name))
	{
	    if (key_exists($name, $this->fields))
	    {
		$valid= true;
		$this->getField($name)->setValue($value);
		$this->modified= true;
	    }
	    if( $this->isExtended() and $this->extends->hasField($name))
	    {
		$this->extends->{$name}= $value;
	    }
	}
	else
	{
	    throw new Exception ("Model '".get_class($this)."' does not contain field '$name'.");
	}
    }


    /**
     * Magic method for getting field's values
     * @param string $name
     * @return mixed
     */
    public function &__get($name)
    {
	$name= strtolower($name);
	if ($this->hasField($name))
	{
	    if (key_exists($name, $this->fields)){
		$field= $this->getField($name);
		if( $field->getIdent() == PerfORM::DateTimeField ||
		    $field->getIdent() == PerfORM::TimeField ||
		    $field->getIdent() == PerfORM::DateField
		    )
		{
		    return $field;
		}
		elseif(
		    $field->getIdent() == PerfORM::ForeignKeyField &&
		    $field->isEnabledLazyLoading() &&
		    !is_null($lazyLoadingKeyValue = $field->getValue())
		)
		{
		    $referenceModel= get_class($field->getReference());
		    $model= new $referenceModel;
		    $model->objects()->load('id=%i', $lazyLoadingKeyValue);
		    $field->setValue($model);
		    $field->disableLazyLoading();
		}
		$result= $field->getValue();
		return $result;
	    }
	    elseif( $this->isExtended())
	    {
		$result= $this->extends->__get($name);
		return $result;
	    }
	    else
	    {
		throw new Exception('logic exception');
	    }
	}
	else
	{
	    throw new Exception ("Model '".get_class($this)."' does not contain field '$name'.");
	}
    }

    /**
     * Model needs object to string conversion
     * @abstract
     */
    abstract function  __toString();

    public function __destruct()
    {
	unset($this->fields);
    }

}
