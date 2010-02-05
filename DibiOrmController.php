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
 * DibiOrmController
 *
 * Enviroment for DibiOrm:
 *  - database operations
 *  - models collection
 *  - configuration options
 *  - robot loader
 *  - driver and dibi connection
 *  - cache
 *  - sql buffer for pretty examples
 *
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

final class DibiOrmController
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
     * Instance of DibiDriver
     * @var DibiOrmPostgreDriver
     */
    protected static $driver;


    /**
     * Collection of all application models
     * @var array
     */
    protected static $models= null;


    /**
     * Instance of DibiOrmModelCacheBuilder
     * @var DibiOrmModelCacheBuilder
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
     * Adds sql query to buffer
     * @param string $sql
     */
    public static function addSql($sql)
    {
	self::$sqlBuffer[]= $sql;
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
	self::getConnection()->begin();
	self::getConnection()->query($sql);
	self::getConnection()->commit();
    }


    /**
     * Getter for Cache instance
     *
     * Creates instance if called for the first time
     * Creates MemcachedStorage if extension memcache exists
     * Otherwise FileStorage Cache is created
     *
     * Triggers notice if config variable advertisememcached in dibiorm block is not set to false
     *
     * @return Cache
     */
    public static function getCache()
    {
	if ( !self::$cache)
	{
	    $config= Environment::getConfig('dibiorm');
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
     * Getter for DibiOrm driver
     * @return DibiOrmDriver
     */
    public static function getDriver()
    {
	if ( !self::$driver)
	{
	    $driverName= self::getConnection()->getConfig('driver');
	    $driverClassName= 'DibiOrm'.ucwords($driverName).'Driver';
	    if ( !class_exists($driverClassName))
	    {
		throw new Exception("driver for '$driverName' not found");
	    }
	    self::$driver= new $driverClassName;
	}
	return self::$driver;
    }


    /**
     * Getter for all models in application
     * @return array
     */
    public static function getModels()
    {
	if ( is_null(self::$models))
	{
	    self::$cacheBuilder= new DibiOrmModelCacheBuilder();
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
	return (empty($buffer)) ? false : $buffer;
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
	    self::getDriver()->appendTableToCreate($model);
	}
	return self::getDriver()->buildSql();
    }


    /**
     * Models operation for clearing database structure for defined models
     *
     * If confirm is set, sql code will be executed
     *
     * @param boolean> $confirm
     * @return string
     */
    public static function sqlclear($confirm = false)
    {
	$storage= new DibiOrmStorage();
	$storage->begin();
	
	foreach( self::getModels() as $model)
	{
	    if ( $storage->hasModel($model) )	    
	    {
		$storage->dropModel($model);
	    }
	}

	$sql= $storage->process();

	if ( !is_null($sql) && $confirm )
	{
	    self::execute($sql);
	}

	$confirm ? $storage->commit() : $storage->rollback();
	return $sql;
    }


    /**
     * Models operation for syncing database structure with defined models
     *
     * If confirm is set, sql code will be executed
     *
     * @todo syncmodels
     * @param boolean $confirm
     * @return string
     */
    public static function syncdb($confirm = false)
    {
	$storage= new DibiOrmStorage();
	$storage->begin();

	/* first run: check models against storage, finds models to create and models to alter */
	# Debug::consoleDump(self::getModels());
	foreach( self::getModels() as $model)
	{
	    # model exists
	    if ( $storage->hasModel($model) )
	    {
		# model out of sync
		if ( !$storage->modelHasSync($model))
		{
		    # checking fields in model against storage
		    foreach( $model->getFields() as $field)
		    {
			# field exists
			if ( $storage->modelHasField($model, $field))
			{

			    if ( !$storage->fieldHasSync($model, $field))
			    {
				$ident= $model->getTableName().'-'.$field->getName().'-'.$field->getHash();
				$columnInfo= self::getConnection()->getDatabaseInfo()->getTable($model->getTableName())->getColumn($field->getName());

				# changing null to not null
				if ( !$field->isNullable() and $columnInfo->isNullable() )
				{
				    $storage->changeFieldToNotNullable($field, $model);
				}

				# changing not null to null
				if ( $field->isNullable() and !$columnInfo->isNullable() )
				{
				    $storage->changeFieldToNullable($field, $model);
				}


				//Debug::consoleDump($columnInfo->getType(), $ident . ' type');
				//Debug::consoleDump($field->getType(), $ident . ' orm type');
				//Debug::consoleDump($columnInfo->getSize(), $ident . ' orm type');
				//Debug::consoleDump($columnInfo->isNullable(), $ident . ' db nullable');
				//Debug::consoleDump($field->isNullable(), $ident . ' orm nullable');
				//Debug::consoleDump($columnInfo);
			    }

			    # TODO checking it's param changes
			}
			else {
			    $storage->addFieldToModel($field, $model);
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
		}
	    }
	    # model does not exists, create
	    else
	    {
		$storage->insertModel($model);
	    }
	}

	/* second run: check storage against models, finds models to drop */
	foreach( $storage->getModels() as $storageModel)
	{
	    if ( !key_exists($storageModel->name, self::getModels()))
	    {
		$storage->dropModel($storageModel->name);
	    }
	}

	$sql= $storage->process();

	if ( !is_null($sql) && $confirm )
	{
	    self::execute($sql);
	}

	$confirm ? $storage->commit() : $storage->rollback();
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
