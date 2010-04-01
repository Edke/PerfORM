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
 * PerfORMController
 *
 * Enviroment for PerfORM:
 *  - database operations
 *  - models collection
 *  - configuration options
 *  - builder loader
 *  - driver and dibi connection
 *  - cache
 *  - sql buffer for pretty examples
 *
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

final class PerfORMController
{


    /**
     * Instance of cache used by models
     * @var Cache
     */
    protected static $cache;


    /**
     * Instance of dibi connection used by models and controler
     * @var DibiConnection
     */
    protected static $connection;


    /**
     * Collection of all application models
     * @var array
     */
    protected static $models= null;


    /**
     * Instance of PerfORMModelCacheBuilder
     * @var PerfORMModelCacheBuilder
     */
    protected static $cacheBuilder;


    /**
     * Array of executed sql queries, used for prettier examples
     * @var array
     */
    protected static $sqlBuffer= array();


    /**
     * Controls whether model uses cache or not
     * @var boolean
     */
    protected static $modelCaching= true;


    /**
     * Array of instances of PerfORMSqlBuilders
     * @var array
     */
    protected static $sqlBuilders= array();


    /**
     * Adds sql query to buffer
     * @param string $sql
     */
    public static function addSql($sql)
    {
	if ( !empty($sql))
	{
	    self::$sqlBuffer[]= $sql;
	}
    }


    /**
     * Disables model caching
     */
    public static function disableModelCaching()
    {
	self::$modelCaching= false;
    }


    /**
     * Enables model caching
     */
    public static function enableModelCaching()
    {
	self::$modelCaching= true;
    }


    /**
     * Executes sql query in transaction
     * @param string $sql
     */
    protected static function execute($sql)
    {
	self::getConnection()->nativeQuery($sql);
    }


    /**
     * Getter for Cache instance
     * Creates instance if called for the first time
     * Creates MemcachedStorage if extension memcache exists
     * Otherwise FileStorage Cache is created
     * Triggers notice if config variable advertisememcached in perform block is not set to false
     * @return Cache
     */
    public static function getCache()
    {
	if ( !self::$cache)
	{
	    $config= Environment::getConfig('perform');
	    if (extension_loaded('memcache'))
	    {
		self::$cache= new Cache(new MemcachedStorage($config->memcache_host, $config->memcache_port, $config->cache_prefix));
		$namespace= self::$cache;
		$namespace['test']= true;
		if ( $namespace['test'] === true)
		{
		    return self::$cache;
		}
	    }

	    self::$cache= Environment::getCache($config->cache_prefix);
	    if ($config->advertisememcached) trigger_error("FileCache enabled, use Memcached if possible. Turn off this warning by setting advertisememcached to false", E_USER_WARNING);
	}
	return self::$cache;
    }


    /**
     * Getter for dibi connection
     * @return DibiConnection
     */
    public static function getConnection()
    {
	if ( !self::$connection)
	{
	    self::$connection= dibi::getConnection();
	}
	return self::$connection;
    }


    /**
     * Getter for SqlBuilder with $name
     * @param string $name
     * @return PerfORMSqlBuilder
     */
    public static function getBuilder($name = 'default')
    {
	if ( !key_exists($name, self::$sqlBuilders))
	{
	    $builderClassName= self::getBuilderName();
	    self::$sqlBuilders[$name]= new $builderClassName;
	}
	return self::$sqlBuilders[$name];
    }


    /**
     * Getter for SqlBuilder class name
     * @return string
     */
    protected static function getBuilderName($driverName = null)
    {
	if ( is_null($driverName))
	{
	    $driverName= self::getConnection()->getConfig('driver');
	}
	$builderClassName= 'PerfORM'.ucwords($driverName).'Builder';
	if ( !class_exists($builderClassName))
	{
	    throw new Exception("builder for driver '$driverName' not found");
	}
	return $builderClassName;
    }


    /**
     * Getter for all models in application
     * @return array
     */
    public static function getModels()
    {
	if ( is_null(self::$models))
	{
	    self::$cacheBuilder= new PerfORMModelCacheBuilder();
	    self::$cacheBuilder->addDirectory(APP_DIR);
	    self::$cacheBuilder->rebuild();
	    self::$models= self::$cacheBuilder->getModels();
	}
	return self::$models;
    }


    /**
     * Getter and cleaner for all sql queries in buffer
     * @return array|boolean
     */
    public static function getSqlBufferAndClear()
    {
	$buffer= self::$sqlBuffer;
	self::$sqlBuffer= array();
	return ( is_array($buffer) and count($buffer)>0 ) ? $buffer : false;
    }


    /**
     * Runs query and inserts it's sql code in buffer
     */
    public static function queryAndLog()
    {
	$args= func_get_args();
	self::getConnection()->query($args);
	self::addSql(dibi::$sql);
    }


    /**
     * Models operation for showing database structure for defined models
     * @return string
     */
    public static function sqlall()
    {
	foreach( self::getModels() as $model)
	{
	    self::getBuilder()->createTable($model);
	    foreach($model->getIndexes() as $index)
	    {
		self::getBuilder()->createIndex($index);
	    }
	}
	return self::getBuilder()->getSql();
    }


    /**
     * Models operation for clearing database structure for defined models
     * If confirm is set, sql code will be executed
     * @param boolean> $confirm
     * @return string
     */
    public static function sqlclear($confirm = false)
    {
	$storage= new PerfORMStorage();
	$storage->begin();
	
	foreach( self::getModels() as $model)
	{
	    if ( $storage->hasModel($model) )	    
	    {
		$model->isTable() ? $storage->dropTable($model) : $storage->dropView($model);
	    }
	}

	$sql= $storage->process();

	if ( !is_null($sql) && $confirm )
	{
	    self::getConnection()->begin();
	    self::execute($sql);
	    self::getConnection()->commit();
	}

	$confirm ? $storage->commit() : $storage->rollback();
	return $sql;
    }

    /**
     * Creates storage for current modelset and ignores database structure
     * If confirm is set, sql code for storage will be executed
     * @param boolean $confirm
     * @return boolean
     */
    public static function sqlset($confirm = false)
    {
	$storage= new PerfORMStorage();
	$storage->begin();

	$storage->trash();
	foreach( self::getModels() as $model)
	{
	    $model->isTable() ? $storage->insertTable($model) : $storage->insertView($model);
	}
	
	# create index storage info
	foreach($model->getIndexes() as $index)
	{
	    $storage->addIndexToModel($index);
	}

	$storage->set();
	$confirm ? $storage->commit() : $storage->rollback();
    }


    /**
     * Models operation for syncing database structure with defined models
     * If confirm is set, sql code will be executed
     * @param boolean $confirm
     * @return string
     */
    public static function syncdb($confirm = false)
    {
	$storage= new PerfORMStorage();
	$storage->begin();
	self::getConnection()->begin();

	/* first run: check models against storage, finds models to create and models to alter */
	foreach( self::getModels() as $model)
	{
	    # model exists
	    if ( $storage->hasModel($model) )
	    {
		# model out of sync
		if ( !$storage->modelHasSync($model) && $model->isTable() )
		{
		    # checking fields in model against storage
		    foreach( $model->getFields() as $field)
		    {
			# field exists
			if ( $storage->fieldBelongsToItsModel($field))
			{
			    # field out of sync
			    if ( !$storage->fieldHasSync($field))
			    {
				#$ident= $model->getTableName().'-'.$field->getName().'-'.$field->getHash();
				$columnInfo= self::getConnection()->getDatabaseInfo()->getTable($model->getTableName())->getColumn($field->getName());

				//if (get_class($field) == 'DecimalField' ) Debug::barDump($columnInfo->getSize(), $ident);

				# changing datatype
				if ( !self::getBuilder()->hasNativeType($field, $columnInfo->getNativeType() ) or
				    ($field->getType() == $columnInfo->getType() and $field->getType() == 's' and $field->getSize() != $columnInfo->getSize()) or
				    (get_class($field) == 'DecimalField' and $columnInfo->getNativeType() == 'NUMERIC' and
					($field->getDigits() != $columnInfo->getVendorInfo('numeric_precision') or
					 $field->getDecimals() != $columnInfo->getVendorInfo('numeric_scale')) )
				    )
				{
				    $storage->changeFieldType($field);
				}
				# other changes
				else {
				    # changing null to not null
				    if ( !$field->isNullable() and $columnInfo->isNullable() )
				    {
					$storage->changeFieldToNotNullable($field);
				    }

				    # changing not null to null
				    if ( $field->isNullable() and !$columnInfo->isNullable() )
				    {
					$storage->changeFieldToNullable($field);
				    }

				    # changing default value
				    if ( self::getBuilder()->translateDefault($field) != $columnInfo->getDefault() )
				    {
					$storage->changeFieldDefaultValue($field);
				    }
				}
			    }
			}
			else {
			    $storage->addFieldToModel($field);
			}
		    }

		    # checking storage against model
		    foreach( $storage->getModelFields($model) as $storageField)
		    {
			if ( !key_exists($storageField->name, $model->getFields() ))
			{
			    $storage->dropFieldFromModel($storageField->name, $model);
			}
		    }

		    # checking storage indexes against model
		    foreach( $storage->getModelIndexes($model) as $storageIndex)
		    {
			if ( !key_exists($storageIndex->name, $model->getIndexes() ))
			{
			    $storage->dropIndexFromModel($storageIndex->name, $model);
			}
		    }

		    # checking indexes in model against storage
		    foreach($model->getIndexes() as $index)
		    {
			# index does not exists
			if ( $model->isTable() && !$storage->modelHasIndex($index))
			{
			    $storage->addIndexToModel($index);
			}
		    }
		}
	    }
	    # model does not exists, create
	    else
	    {
		$model->isTable() ? $storage->insertTable($model) : $storage->insertView($model);
	    }
	}

	/* second run: check storage against tables, finds tables to drop */
	foreach( $storage->getTables() as $storageTable)
	{
	    if ( !key_exists($storageTable->name, self::getModels()))
	    {
		$storage->dropTable($storageTable->name);
	    }
	}

	/* third run: check storage against views, finds views to drop */
	foreach( $storage->getViews() as $storageView)
	{
	    if ( !key_exists($storageView->name, self::getModels()))
	    {
		$storage->dropView($storageView->name);
	    }
	}

	$sql= $storage->process();
	if ( !is_null($sql) && $confirm )
	{
	    self::execute($sql);
	    self::getConnection()->commit();
	    $storage->commit();
	}
	else {
	    self::getConnection()->rollback();
	    $storage->rollback();
	}
	return $sql;
    }


    /**
     * Getter which controls if models will use caching
     * @return boolean
     */
    public static function useModelCaching()
    {
	return self::$modelCaching;
    }
}
