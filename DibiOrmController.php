<?php
/**
 * controler
 *
 * @author kraken
 */
class DibiOrmController
{
    protected static $models= null;

    /**
     * @var DibiConnection
     */
    protected static $connection;

    /**
     * @var DibiOrmPostgreDriver
     */
    protected static $driver;

    protected static $sqlBuffer= array();

    protected static $robot;

    /**
     * @var Cache
     */
    protected static $cache;

    public static function getModels()
    {
	if ( is_null(self::$models))
	{
	    self::$models= self::getModelLoader()->getModels();
	}
	return self::$models;
    }

    public static function getModelMtime($model)
    {
	$key= strtolower(get_class($model));
	return self::getModelLoader()->getModelMtime($key);
    }

    /**
     * @return DibiOrmModelLoader
     */
    public static function getModelLoader()
    {
	if ( is_null(self::$robot))
	{
	    self::$robot= new DibiOrmModelLoader();
	    self::$robot->addDirectory(APP_DIR);
	    self::$robot->rebuild();
	}
	return self::$robot;
    }


    /**
     * Vytvori/vrat instanciu pre Cache so storage MemcachedStorage()
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
		if ( $namespace['test'] === true) {
		    return self::$cache;
		}
	    }

	    self::$cache= Environment::getCache($config->cache_prefix);
	    if ($config->advertisememcached) trigger_error("FileCache enabled, use Memcached if possible. Turn off this warning by setting advertisememcached to false", E_USER_WARNING);
	}
	return self::$cache;
    }


    /**
     * @return DibiOrmPostgreDriver
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

    public static function syncdb($confirm = false)
    {
	$sql= null;
	$syncModels= array();
	$createModels= array();
	foreach( self::getModels() as $model)
	{
	    if ( $model->getConnection()->getDatabaseInfo()->hasTable($model->getTableName()) )
	    {
		$syncModels[]= $model;
	    }
	    else
	    {
		$createModels[]= $model;
	    }
	}

	# createModels
	self::dependancySortReverse($createModels);
	foreach($createModels as $model)
	{
	    $sql .= $model->getDriver()->createTable($model);
	}

	#TODO syncmodels
	// $model->getDriver()->syncTable($model);

	if ( !is_null($sql) && $confirm )
	{
	    self::execute($sql);
	}
	return $sql;
    }

    public static function sqlall()
    {
	$sql= null;
	foreach( self::getModels() as $model)
	{
	    $sql .= $model->getDriver()->createTable($model);
	}
	return $sql;
    }

    public static function sqlclear($confirm)
    {
	$sql= null;
	$models = array();

	foreach( self::getModels() as $model)
	{
	    if ( $model->getConnection()->getDatabaseInfo()->hasTable($model->getTableName()) )
	    {
		$models[]= $model;
	    }
	}
	self::dependancySort($models);
	foreach($models as $model)
	{
	    $sql .= $model->getDriver()->dropTable($model);
	}

	if ( !is_null($sql) && $confirm )
	{
	    self::execute($sql);
	}
	return $sql;
    }

    protected static function execute($sql)
    {
	self::getConnection()->begin();
	self::getConnection()->query($sql);
	self::getConnection()->commit();
    }

    protected static function dependancySortReverse( &$list)
    {
	self::dependancySort($list);
	$list= array_reverse($list);
    }

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

    public static function queryAndLog()
    {
	$args= func_get_args();
	self::getConnection()->query($args);
	self::addSql(dibi::$sql); //log sql
    }



    protected static function insertArrayIndex($array, $new_element, $index)
    {
	$start = array_slice($array, 0, $index);
	$end = array_slice($array, $index);
	$start[] = $new_element;
	return array_merge($start, $end);
    }

    public static function addSql($sql)
    {
	self::$sqlBuffer[]= $sql;
    }

    /**
     * @return array|boolean
     */
    public static function getSqlBufferAndClear()
    {
	$buffer= self::$sqlBuffer;
	self::$sqlBuffer= array();
	return (empty($buffer)) ? false : $buffer;
    }
}