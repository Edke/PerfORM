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
     * Models with dependancy defined are moved before its dependents
     * @param array $list
     */
    protected static function dependancySort( &$list)
    {
	$_list= $list;
	foreach($_list as $item)
	{
	    $min= null;
	    foreach($item->getDependents() as $dependent)
	    {
		$min= ( is_null($min )) ? array_search($dependent, $_list) : min($min, array_search($dependent, $_list));
	    }
	    if (is_integer($min))
	    {
		if ( array_search($item, $_list) > $min)
		{
		    unset($list[array_search($item,$list)]);
		    $list= self::insertArrayIndex($list, $item, $min);
		}
	    }
	}
    }


    /**
     * Reversed sort of dependancySort method
     * @param array $list
     */
    protected static function dependancySortReverse( &$list)
    {
	self::dependancySort($list);
	$list= array_reverse($list);
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
     * Inserts element at $index of $array
     * @param array $array
     * @param mixed $new_element
     * @param integer $index
     * @return array
     */
    protected static function insertArrayIndex($array, $new_element, $index)
    {
	$start = array_slice($array, 0, $index);
	$end = array_slice($array, $index);
	$start[] = $new_element;
	return array_merge($start, $end);
    }


    /**
     * Flushes cache and rebuilds model's cache
     */
    public static function rebuildCache()
    {
	$cache = self::getCache();
	$cache->clean(array(Cache::ALL));

	foreach(self::getModels() as $model)
	{
	    $new_model= new $model;
	}
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
	self::disableModelCaching();
	$sql= null;
	foreach( self::getModels() as $modelName)
	{
	    $sql .= self::getDriver()->createTable(new $modelName);
	}
	return $sql;
    }


    /**
     * Models operation for clearing database structure for defined models
     *self::disableModelCaching();
     * If confirm is set, sql code will be executed
     *
     * @param boolean> $confirm
     * @return string
     */
    public static function sqlclear($confirm)
    {
	self::disableModelCaching();
	$sql= null;
	$models = array();

	foreach( self::getModels() as $modelName)
	{
	    if ( self::getConnection()->getDatabaseInfo()->hasTable($modelName) )
	    {
		$models[]= new $modelName;
	    }
	}
	self::dependancySort($models);
	foreach($models as $model)
	{
	    $sql .= self::getDriver()->dropTable($model);
	}

	if ( !is_null($sql) && $confirm )
	{
	    self::execute($sql);
	}
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
	#self::disableModelCaching();
	$sql= null;
	$syncModels= array();
	$createModels= array();
	foreach( self::getModels() as $modelInfo)
	{
	    $modelName= $modelInfo->model;
	    if ( self::getConnection()->getDatabaseInfo()->hasTable($modelName) )
	    {
		$syncModels[]= new $modelName;
	    }
	    else
	    {
		$createModels[]= new $modelName;
	    }
	}

	# createModels
	self::dependancySortReverse($createModels);
	foreach($createModels as $model)
	{
	    $sql .= self::getDriver()->createTable($model);
	}

	# syncModels
	foreach($syncModels as $model)
	{
	    $sql .= self::getDriver()->syncTable($model);
	}

	if ( !is_null($sql) && $confirm )
	{
	    self::execute($sql);
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
