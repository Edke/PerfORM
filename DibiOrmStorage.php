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
 * DibiOrmStorage
 *
 * write desc when class ready
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */
final class DibiOrmStorage extends DibiConnection
{


    /**
     * Constractor creates connection and checks/creates tables stucture
     */
    public function  __construct()
    {
	parent::__construct(Environment::getConfig('dibiorm')->storage, 'storage');

	if ( !$this->getDatabaseInfo()->hasTable('fields'))
	{
	    $this->query('CREATE TABLE [fields] ([id] INTEGER NOT NULL PRIMARY KEY,
		[name] VARCHAR(100) NOT NULL, [table] VARCHAR(50) NOT NULL,
		[hash] VARCHAR(32) NOT NULL, [type] VARCHAR(50));
		CREATE UNIQUE INDEX [fields_idx] on [fields] ( [name], [table]);');
	}

	if ( !$this->getDatabaseInfo()->hasTable('tables'))
	{
	    $this->query('CREATE TABLE [tables] ( [id] INTEGER NOT NULL PRIMARY KEY,
		[name] VARCHAR(100) NOT NULL UNIQUE, [hash] VARCHAR(32) NOT NULL);');
	}
    }


    /**
     * Cleares info about table from storage
     * @param string $tableName
     */
    public function dropTable($tableName)
    {
	$this->query('delete from [tables] where [name] = %s', $tableName);
	$this->query('delete from [fields] where [table] = %s', $tableName);
    }


    /**
     * Inserts info about model into storage
     * @param string $tableName
     */
    public function insertTable($modelInfo)
    {
	$this->query('insert into [tables] values( null, %s, %s)', $modelInfo->table, $modelInfo->hash );

	foreach($modelInfo->fields as $field )
	{
	    $this->query('insert into [fields] values( null, %s, %s, %s, %s)',
	    strtolower($field->name),
	    $modelInfo->table,
	    $field->hash,
	    $field->type);
	}
    }


    /**
     * Checks if model in sync
     * @param stdClass $modelInfo
     */
    public function tableInSync($modelInfo)
    {

    }
}
