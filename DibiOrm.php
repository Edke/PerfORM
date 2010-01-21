<?php
/**
 * my dibi orm attempt
 *
 * @author kraken
 */
abstract class DibiOrm
{

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

    /**
     * @var string
     */
    protected $defaultPrimaryKey= 'id';

    /**
     * @var array
     */
    protected $depends= array();

    /**
     * @var boolean
     */
    protected $modified= false;

    /**
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
     * Load model definition from cache if exists; if not, build model
     */
    protected function loadDefinition()
    {
	$cache= DibiOrmController::getCache();
	$cacheKey= $this->getCacheKey();
	if ( isset($cache[$cacheKey]))
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
	
	if (DibiOrmController::useModelCaching()) {
	    $cache= DibiOrmController::getCache();
	    $cache[$this->getCacheKey()]= $this;
	}
    }

    abstract protected function setup();

    public function  __set($field,  $value)
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

    public function  __get($field)
    {
	if ( key_exists($field, $this->fields) && is_object($this->fields[$field]))
	{
	    return $this->fields[$field]->getValue();
	}
	throw new Exception("invalid field name '$field'");
    }

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

    protected function setPrimaryKey($primaryKey)
    {
	$this->primaryKey= $primaryKey;
    }

    public function getTableName()
    {
	return $this->tableName;
    }

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

    public function update()
    {
	$update= array();

	foreach($this->fields as $key => $field)
	{
	    $finalColumn= $field->getRealName().'%'.$field->getType();

	    if ($field->isPrimaryKey())
	    {
		$primaryKey= $field->getRealName();
		$primaryKeyValue= $field->getValue();
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


    public function insert()
    {
	$insert= array();

	foreach($this->fields as $key => $field)
	{
	    $finalColumn= $field->getRealName().'%'.$field->getType();

	    if ( !is_null($value = $field->getValue()) )
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
	    trigger_error("The model '".get_class($this)."' has no unmodified data to insert", E_USER_NOTICE);
	}
    }

    /**
     * @return array
     */
    public function getFields()
    {
	return $this->fields;
    }

    /**
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
     * @return Field
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
     * validate fields definition
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
     *
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

    public function getDependents()
    {
	return $this->depends;
    }


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

    public function getConnection()
    {
	return DibiOrmController::getConnection();
    }


    public function getDriver()
    {
	return DibiOrmController::getDriver();
    }

    /**
     * @return boolean
     */
    public function isModified()
    {
	return $this->modified;
    }

    protected function setUnmodified()
    {
	$this->modified= false;
	foreach( $this->getFields() as $field)
	{
	    $field->setUnmodified();
	}
    }

    public function objects()
    {
	return new QuerySet($this);
    }

    protected function getCacheKey()
    {
	$mtime= DibiOrmController::getModelMtime($this);
	return md5($mtime.'|'.get_class($this));
    }


    {
	return get_object_vars($this);
    }
}
