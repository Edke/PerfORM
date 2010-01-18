<?php
/**
 * controler
 *
 * @author kraken
 */
class DibiOrmController
{
    protected static $models;

    /**
     * @var DibiConnection
     */
    protected static $connection;

    /**
     * @var DibiOrmPostgreDriver
     */
    protected static $driver;
    
    public static function getModels()
    {
	if ( is_null(self::$models)) {
	    $robot = new DibiOrmModelLoader();
	    $robot->addDirectory(APP_DIR);
	    self::$models= $robot->getModels();
	}
	return self::$models;
    }

    /**
     * @return DibiOrmPostgreDriver
     */
    public static function getDriver() {
	if ( !self::$driver) {
	    $driverName= self::getConnection()->getConfig('driver');
	    $driverClassName= 'DibiOrm'.ucwords($driverName).'Driver';
	    if ( !class_exists($driverClassName)) {
		throw new Exception("driver for '$driverName' not found");
	    }
	    self::$driver= new $driverClassName;
	}
	return self::$driver;
    }

    /**
     * @return DibiConnection
     */
    public static function getConnection() {
	if ( !self::$connection) {
	    self::$connection= dibi::getConnection();
	}
	return self::$connection;
    }

    public static function syncdb($confirm = false)
    {
	$sql= null;
	$syncModels= array();
	$createModels= array();
	foreach( self::getModels() as $model) {
	    if ( $model->getConnection()->getDatabaseInfo()->hasTable($model->getTableName()) ) {
		$syncModels[]= $model->getDriver()->syncTable($model);
		$syncModels[]= $model->getDriver()->syncTable($model);
	    }
	    else {
		$createModels[]= $model;
	    }
	}

	#TODO syncmodels

	self::dependancySort($createModels);
	foreach($createModels as $model){
	    $sql .= $model->getDriver()->createTable($model);
	}







	if ( !is_null($sql) && $confirm ) {
	    self::execute($sql);
	}
	return $sql;
    }


    public static function sqlall()
    {
	$sql= null;
	foreach( self::getModels() as $model) {
	    $sql .= $model->getDriver()->createTable($model);
	}
	return $sql;
    }
    
    public static function sqlclear($confirm)
    {
	$sql= null;
	$models = array();

	foreach( self::getModels() as $model) {
	    if ( $model->getConnection()->getDatabaseInfo()->hasTable($model->getTableName()) ) {
		$models[]= $model;
	    }
	}
	self::dependancySort($models);
	foreach($models as $model){
	    $sql .= $model->getDriver()->dropTable($model);
	}

	if ( !is_null($sql) && $confirm ) {
	    self::execute($sql);
	}
	return $sql;
    }

    protected static function execute($sql) {
	self::getConnection()->begin();
	self::getConnection()->query($sql);
	self::getConnection()->commit();
    }

    protected static function dependancySort( &$list)
    {
	$_list= $list;
	foreach($_list as $item) {
	    $min= null;
	    foreach($item->getDependents() as $dependent) {
		$min= ( is_null($min )) ? array_search($dependent, $_list) : min($min, array_search($dependent, $_list));
	    }
	    if (is_integer($min)) {
		unset($list[array_search($item,$list)]);
		$list= self::insertArrayIndex($list, $item, $min);
	    }
	}
    }

    static function insertArrayIndex($array, $new_element, $index) {
	$start = array_slice($array, 0, $index);
	$end = array_slice($array, $index);
	$start[] = $new_element;
	return array_merge($start, $end);
     }
}