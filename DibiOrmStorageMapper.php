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
 * DibiOrmStorageMapper
 *
 * write desc when class ready
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */
final class DibiOrmStorageMapper
{
    /**
     * sqlite resource
     * @var resource
     */
    protected $resource;


    /**
     * Collection of all application models
     * @var array
     */
    protected $models= null;


    /**
     * Instance of DibiOrmModelCacheBuilder
     * @var DibiOrmModelCacheBuilder
     */
    protected static $cacheBuilder;


    public function  __construct()
    {
	if (!extension_loaded('sqlite'))
	{
	    throw new Exception('SQLite extension is required for storing model ....');
	}

	if ( !$this->resource= sqlite_open(Environment::getConfig('dibiorm')->storageDatabase, 0666, $sqlerror))
	{
	    throw new Exception($sqlerror);
	}

	if ( !$this->has_table('fields') ) {
	    $this->query('CREATE TABLE "fields" ("id" INTEGER NOT NULL PRIMARY KEY,
		"name" VARCHAR(100) NOT NULL, "table" VARCHAR(50) NOT NULL,
		"hash" VARCHAR(32) NOT NULL, "type" VARCHAR(50));
		CREATE UNIQUE INDEX "fields_idx" on "fields" ( "name", "table");');
	}

	if ( !$this->has_table('tables') ) {
	    $this->query('CREATE TABLE "tables" ( "id" INTEGER NOT NULL PRIMARY KEY,
		"name" VARCHAR(100) NOT NULL UNIQUE, "hash" VARCHAR(32) NOT NULL);');
	}
    }


    public function begin()
    {
	$this->query('begin transaction;');
    }

    public function rollback()
    {
	$this->query('rollback;');
    }


    public function commit()
    {
	$this->query('commit;');
    }


    protected function query($sql)
    {
	$result= @sqlite_query($this->resource, $sql, SQLITE_BOTH, $error_msg );
	if ($error_msg)
	{
	    throw new Exception($error_msg);
	}

	return $result;
    }


    public function insertTable($modelInfo)
    {
	$tableName= strtolower($modelInfo->model);
	$this->query(sprintf('insert into "tables" values(null, \'%s\', \'%s\')', $tableName, $modelInfo->hash ));

	foreach($modelInfo->fields as $field )
	{
	    $this->query(sprintf('insert into "fields" values(null, \'%s\', \'%s\', \'%s\', \'%s\')',
		strtolower($field->name),
		$tableName,
		$field->hash,
		$field->type));
	}
    }

    public function dropTable($tableName)
    {
	$tableName= strtolower($tableName);
	$this->query('delete from "tables" where "name" = \''. $tableName. '\'');
	$this->query('delete from "fields" where "table" = \''. $tableName. '\'');
    }


    protected function has_table($tablename)
    {
	$tablename= strtolower($tablename);
	$result= $this->query('SELECT "name" FROM "sqlite_master" WHERE "type"= \'table\' and "name"=\''. $tablename .'\'');
	return $result === false ? false : true;
    }


    /**
     * Getter sqlite resource
     * @return resource
     */
    public static function getResource()
    {
	return $this->resource;
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
}
