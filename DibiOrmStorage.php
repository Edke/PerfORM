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

    const TABLE_ADD = 1;
    const TABLE_DROP = 2;
    const FIELD_ADD = 3;
    const FIELD_DROP = 4;

    protected $queue= array();
    protected $renamedModels= array();
    protected $renamedFields= array();


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
     *
     * @param integer $operation
     * @param string $sql
     * @param array $values
     */
    protected function queue($operation, $key, $sql, $values)
    {
	$this->queue[$operation][$key]= (object) array(
	    'sql' => $sql,
	    'values' => $values,
	);
    }

    /**
     * @param Field $field
     * @param DibiOrm $model
     */
    public function addFieldToModel($field, $model)
    {
	$sql= $this->sql('insert into [fields] values( null, %s, %s, %s, %s)',
	    $field->getName(),
	    $model->getTableName(),
	    $field->getHash(),
	    get_class($field));

	$this->queue(
	    DibiOrmStorage::FIELD_ADD,
	    $field->getName().'|'.$model->getTableName(),
	    $sql,
	    array(
		'field' => $field,
		'model' => $model)
	    );
	

	$key= $field->getHash().'|'.$model->getTableName();
	if ( key_exists($key, $this->renamedFields))
	{
	    $array= $this->renamedFields[$key];
	    $array->counter++;
	    $array->to= $field->getName();
	}
	else{
	    $this->renamedFields[$key]= (object) array(
		'counter' => 1,
		'to' => $field->getName(),
		'modelName' => $model->getTableName(),
	    );
	}
    }





    /**
     * @param string $fieldName
     * @param DibiOrm $model
     * @return string
     */
    public function dropFieldFromModel($fieldName, $model)
    {
	$hash= $this->fetchSingle('select hash from [fields] where [name] = %s and [table] = %s',
	    $fieldName,
	    $model->getTableName());
	$sql= $this->sql('delete from [fields] where [name] = %s and [table] = %s',
	    $fieldName,
	    $model->getTableName());

	$this->queue(
	    DibiOrmStorage::FIELD_DROP,
	    $fieldName.'|'.$model->getTableName(),
	    $sql,
	    array(
		'fieldName' => $fieldName,
		'model' => $model)
	    );
	$this->renamedFields[$hash.'|'.$model->getTableName()]++;


	$key= $hash.'|'.$model->getTableName();
	if ( key_exists($key, $this->renamedFields))
	{
	    $array= $this->renamedFields[$key];
	    $array->counter++;
	    $array->from= $fieldName;
	}
	else{
	    $this->renamedFields[$key]= (object) array(
		'counter' => 1,
		'from' => $fieldName,
		'modelName' => $model->getTableName(),
	    );
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
	$hash= $this->fetchSingle('select hash from [tables] where [name] = %s ',
	    $modelName);
	$sql = $this->sql('delete from [tables] where [name] = %s;', $modelName );
	$sql .= $this->sql('delete from [fields] where [table] = %s;', $modelName );

	$this->queue(
	    DibiOrmStorage::TABLE_DROP,
	    $modelName,
	    $sql,
	    array(
		'model' => $model)
	    );


	$key= $hash;
	if ( key_exists($key, $this->renamedModels))
	{
	    $array= $this->renamedModels[$key];
	    $array->counter++;
	    $array->from= $modelName;
	}
	else{
	    $this->renamedModels[$key]= (object) array(
		'counter' => 1,
		'from' => $modelName,
	    );
	}
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
     * Get all fields of model from storage
     * @param DibiOrm $model
     * @return DibiResult
     */
    public function getModelFields($model)
    {
	return $this->query('select [name] from [fields] where [table] = %s', $model->getTableName() );
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
	$modelName= is_object($model) ? $model->getTableName() : $model;
	$sql = $this->sql('insert into [tables] values( null, %s, %s);', $model->getTableName(), $model->getHash() );
	foreach($model->getFields() as $field )
	{
	    $sql .= $this->sql('insert into [fields] values( null, %s, %s, %s, %s);',
	    strtolower($field->getName()),
	    $model->getTableName(),
	    $field->getHash(),
	    get_class($field));
	}
	$this->queue(
	    DibiOrmStorage::TABLE_ADD,
	    $modelName,
	    $sql,
	    array(
		'model' => $model)
	    );
	
	$key= $model->getHash();
	if ( key_exists($key, $this->renamedModels))
	{
	    $array= $this->renamedModels[$key];
	    $array->counter++;
	    $array->to= $modelName;
	}
	else{
	    $this->renamedModels[$key]= (object) array(
		'counter' => 1,
		'to' => $modelName,
	    );
	}
    }


    /**
     * Storage processing
     * @return string
     */
    public function process()
    {
	//Debug::consoleDump($this->renamedFields,'renamed fields');
	Debug::consoleDump($this->renamedModels,'renamed models');
	Debug::consoleDump($this->queue,'queue');

	# renamed fields, remove adds and drops
	foreach( $this->renamedFields as $key => $array)
	{
	    if ( $array->counter == 2 and isset($array->from) and isset($array->to) and isset($array->modelName))
	    {
		$model= $this->queue[DibiOrmStorage::FIELD_ADD][$array->to.'|'.$array->modelName]->values['model'];
		$field= $this->queue[DibiOrmStorage::FIELD_ADD][$array->to.'|'.$array->modelName]->values['field'];
		unset($this->queue[DibiOrmStorage::FIELD_ADD][$array->to.'|'.$array->modelName]);
		unset($this->queue[DibiOrmStorage::FIELD_DROP][$array->from.'|'.$array->modelName]);

		$this->query('update [fields] set [name] = %s where [name] = %s and [table] = %s',
		    $array->to,
		    $array->from,
		    $array->modelName);

		DibiOrmController::getDriver()->appendFieldToRename($field, $array->from, $model);
	    }
	}


	# renamed tables, remove adds and drops
	foreach( $this->renamedModels as $key => $array)
	{
	    if ( $array->counter == 2 and isset($array->from) and isset($array->to))
	    {
		$model= $this->queue[DibiOrmStorage::TABLE_ADD][$array->to]->values['model'];
		unset($this->queue[DibiOrmStorage::TABLE_ADD][$array->to]);
		unset($this->queue[DibiOrmStorage::TABLE_DROP][$array->from]);

		$this->query('update [tables] set [name] = %s where [name] = %s',
		    $array->to,
		    $array->from);

		DibiOrmController::getDriver()->appendTableToRename($model, $array->from);
	    }
	}


	foreach( $this->queue as $operation => $actions)
	{
	    foreach($actions as $key => $action)
	    {
		list($field, $table)= explode('|', $key);

		switch($operation)
		{
		    case DibiOrmStorage::FIELD_ADD:
			DibiOrmController::getDriver()->appendFieldToAdd($action->values['field'], $action->values['model']);
			break;

		    case DibiOrmStorage::FIELD_DROP:
			DibiOrmController::getDriver()->appendFieldToDrop($action->values['fieldName'], $action->values['model']);
			break;

		    case DibiOrmStorage::TABLE_ADD:
			DibiOrmController::getDriver()->appendTableToCreate($action->values['model']);
			break;

		    case DibiOrmStorage::TABLE_DROP:
			DibiOrmController::getDriver()->appendTableToDrop($action->values['model']);
			break;
		}
		$this->query($action->sql);
	    }
	}

	return DibiOrmController::getDriver()->buildSql();
    }


    /**
     * Checks if model has field
     * @param DibiOrm $model
     * @param Field $field
     * @return boolean
     */
    public function modelHasField($model, $field)
    {
	return $this->fetch('select [id] from [fields] where [table] = %s and [name] = %s', $model->getTableName(), $field->getName()) === false ? false : true;
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
