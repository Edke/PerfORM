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
     * @param DibiOrm|string $model
     * @return void
     */
    public function dropModel($model)
    {
	$modelName= is_object($model) ? $model->getTableName() : $model;
	$this->query('delete from [tables] where [name] = %s', $modelName );
	$this->query('delete from [fields] where [table] = %s', $modelName );
    }


    /**
     * Get all models from storage
     * @return DibiResult
     */
    public function getModels()
    {
	return $this->query('select [name] from [tables]');
    }

    
    /**
     * Checks if model exists
     * @param DibiOrm $model
     * @return boolean
     */
    public function hasModel($model)
    {
	return $this->fetch('select [id] from [tables] where [name] = %s', $model->getTableName()) === false ? false : true;
    }


    /**
     * Inserts model's name and hash into storage
     * @param DibiOrm $model
     */
    public function insertModel($model)
    {
	$this->query('insert into [tables] values( null, %s, %s)', $model->getTableName(), $model->getHash() );

	foreach($model->getFields() as $field )
	{
	    $this->query('insert into [fields] values( null, %s, %s, %s, %s)',
	    strtolower($field->getName()),
	    $model->getTableName(),
	    $field->getHash(),
	    get_class($field));
	}
    }
    

    /**
     * Checks if model has field
     * @param Field $field
     * @return boolean
     */
    public function modelHasField($field)
    {
	return $this->fetch('select [id] from [fields] where [table] = %s and [name] = %s', $field->table, $fields->name) === false ? false : true;
    }


    /**
     * Checks if model in sync
     * @param DibiOrm $model
     * @return boolean
     */
    public function modelHasSync($model)
    {
	return $this->fetch('select [id] from [tables] where [name] = %s and [hash] = %s', $model->getTableName(), $model->getHash()) === false ? false : true;
    }
}
