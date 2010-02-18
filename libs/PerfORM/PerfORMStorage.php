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
 * PerfORMStorage
 *
 * write desc when class ready
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

final class PerfORMStorage extends DibiConnection
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
	parent::__construct(Environment::getConfig('perform')->storage, 'storage');

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
     * Adds column to table
     * @param Field $field
     */
    public function addFieldToModel($field)
    {
	$sql= $this->sql('insert into [fields] values( null, %s, %s, %s, %s)',
	    $field->getName(),
	    $field->getModel()->getTableName(),
	    $field->getHash(),
	    get_class($field));

	$this->queue(
	    PerfORMStorage::FIELD_ADD,
	    $field->getName().'|'.$field->getModel()->getTableName(),
	    $sql,
	    array(
		'field' => $field,
		)
	);

	$key= $field->getHash().'|'.$field->getModel()->getTableName();
	if ( key_exists($key, $this->renamedFields))
	{
	    $array= $this->renamedFields[$key];
	    $array->counter++;
	    $array->to= $field->getName();
	}
	else
	{
	    $this->renamedFields[$key]= (object) array(
	    'counter' => 1,
	    'to' => $field->getName(),
	    'modelName' => $field->getModel()->getTableName(),
	    );
	}
    }



    /**
     * Change column's default value
     * @param Field $field
     */
    public function changeFieldDefaultValue($field)
    {
	PerfORMController::getBuilder()->changeFieldsDefault($field);
	$this->updateFieldSync($field);
    }


    /**
     * Change column from null to not null
     * @param Field $field
     */
    public function changeFieldToNotNullable($field)
    {
	$result= PerfORMController::getConnection()->query('select * from %n where %n is null',
	    $field->getModel()->getTableName(),
	    $field->getName()
	);

	$pk= $field->getModel()->getPrimaryKey();

	foreach($result as $row )
	{
	    if ( !is_null($value= $field->getDefaultValue()) )
	    {
	    }
	    elseif ( is_callable($field->getDefaultCallback()))
	    {
		$value= call_user_func($field->getDefaultCallback(), $row);
	    }
	    else
	    {
		throw new Exception("Unable to set default value for field '".$field->getName()."'");
	    }

	    PerfORMController::getConnection()->query('update %n set %n = %'.$field->getType().' where %n = %i',
	    $field->getModel()->getTableName(),
	    $field->getName(),
	    $value,
	    $pk,
	    $row->{$pk}
	    );
	}

	PerfORMController::getBuilder()->changeFieldsNullable($field);
	$this->updateFieldSync($field);
    }


    /**
     * Change column from not null to null
     * @param Field $field
     */
    public function changeFieldToNullable($field)
    {
	PerfORMController::getBuilder()->changeFieldsNullable($field);
	$this->updateFieldSync($field);
    }

    /**
     * Change column from null to not null
     * @param Field $field
     */
    public function changeFieldType($field)
    {
	if ( !$field->getRecastCallback())
	{
	    throw new Exception("Unable to recast value as recast callback was not set for field '".$field->getName()."'");
	}

	$builder= PerfORMController::getBuilder('fieldretype');
	
	$tmpfield= $field->getName().'_'.md5(time());
	$template= $builder->getTemplate('field-add');
	$fieldInfo= $builder->getField($field);
	$fieldInfo->name= $tmpfield;
	$fieldInfo->nullable= true;
	$template->field= $fieldInfo;
	PerfORMController::getBuilder()->addToBuffer($builder->renderTemplate($template));
	//PerfORMController::getConnection()->nativeQuery($sql);

	$result= PerfORMController::getConnection()->query('select * from %n',
	    $field->getModel()->getTableName()
	);

	$pk= $field->getModel()->getPrimaryKey();

	foreach($result as $row )
	{
	    if ( is_callable($field->getRecastCallback()))
	    {
		$value= call_user_func($field->getRecastCallback(), $row);
		if ( !$field->isNullable() and is_null($value))
		{
		    throw new Exception("Unable to recast null value for field '".$field->getName()."' (id=".$row->{$pk}.") as field is not null");
		}
	    }
	    else
	    {
		throw new Exception("Unable to recast value for field '".$field->getName()."' (id=".$row->{$pk}.")");
	    }

	    $sql= PerfORMController::getConnection()->sql('update %n set %n = %'.$field->getType().' where %n = %i;',
		$field->getModel()->getTableName(),
		$tmpfield,
		$value,
		$pk,
		$row->{$pk}
	    );
	    PerfORMController::getBuilder()->addToBuffer($sql);
	}

	if (!$field->isNullable())
	{
	    $fieldInfo->nullable= false;
	    $template= $builder->getTemplate('field-change-nullable');
	    $template->field= $fieldInfo;
	    $sql= $builder->renderTemplate($template);
	    PerfORMController::getBuilder()->addToBuffer($sql);
	    //PerfORMController::getConnection()->nativeQuery($sql);
	}

	PerfORMController::getBuilder()->dropField($field->getName(), $field->getModel());
	PerfORMController::getBuilder()->renameField($field, $tmpfield);
	$this->updateFieldSync($field);
    }


    /**
     * Drops column from table
     * @param string $fieldName
     * @param PerfORM $model
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
	    PerfORMStorage::FIELD_DROP,
	    $fieldName.'|'.$model->getTableName(),
	    $sql,
	    array(
        	'fieldName' => $fieldName,
		'model' => $model
	    )
	);
	$key= $hash.'|'. $model->getTableName();
	if ( key_exists($key, $this->renamedFields))
	{
	    $array= $this->renamedFields[$key];
	    $array->counter++;
	    $array->from= $fieldName;
	}
	else
	{
	    $this->renamedFields[$key]= (object) array(
	    'counter' => 1,
	    'from' => $fieldName,
	    'modelName' => $model->getTableName(),
	    );
	}
    }


    /**
     * Drops table from database
     * @param PerfORM|string $model
     * @return void
     */
    public function dropModel($model)
    {
	$modelName= is_object($model) ? $model->getTableName() : $model;
	$hash= $this->fetchSingle('select hash from [tables] where [name] = %s ',
	    $modelName);
	$sql= array();
	$sql[] = $this->sql('delete from [tables] where [name] = %s;', $modelName );
	$sql[] = $this->sql('delete from [fields] where [table] = %s;', $modelName );

	$this->queue(
	    PerfORMStorage::TABLE_DROP,
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
	else
	{
	    $this->renamedModels[$key]= (object) array(
	    'counter' => 1,
	    'from' => $modelName,
	    );
	}
    }


    /**
     * Checks if column in sync
     * @param Field $field
     * @return boolean
     */
    public function fieldHasSync($field)
    {
	return $this->fetch('select [id] from [fields] where [name] = %s and [table] = %s and [hash] = %s',
	    $field->getName(),
	    $field->getModel()->getTableName(),
	    $field->getHash()
	) === false ? false : true;
    }


    /**
     * Get all fields of model from storage
     * @param PerfORM $model
     * @return DibiResult
     */
    public function getModelFields($model)
    {
	return $this->query('select [name] from [fields] where [table] = %s', $model->getTableName() );
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
     * @param PerfORM $model
     * @return boolean
     */
    public function hasModel($model)
    {
	return $this->fetch('select [id] from [tables] where [name] = %s', $model->getTableName()) === false ? false : true;
    }


    /**
     * Inserts model's name and hash into storage
     * @param PerfORM $model
     */
    public function insertModel($model)
    {
	$modelName= is_object($model) ? $model->getTableName() : $model;
	$sql=array();
	$sql[] = $this->sql('insert into [tables] values (null, %s, %s);', $model->getTableName(), $model->getHash() );
	foreach($model->getFields() as $field )
	{
	    $sql[] = $this->sql('insert into [fields] values (null, %s, %s, %s, %s);',
	    strtolower($field->getName()),
	    $model->getTableName(),
	    $field->getHash(),
	    get_class($field));
	}
	$this->queue(
	    PerfORMStorage::TABLE_ADD,
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
	else
	{
	    $this->renamedModels[$key]= (object) array(
	    'counter' => 1,
	    'to' => $modelName,
	    );
	}
    }


    /**
     * Checks if field belongs to it's model
     * @param Field $field
     * @return boolean
     */
    public function fieldBelongsToItsModel($field)
    {
	return $this->fetch('select [id] from [fields] where [table] = %s and [name] = %s', 
	    $field->getModel()->getTableName(),
	    $field->getName()
	) === false ? false : true;
    }


    /**
     * Checks if model in sync
     * @param PerfORM $model
     * @return boolean
     */
    public function modelHasSync($model)
    {
	return $this->fetch('select [id] from [tables] where [name] = %s and [hash] = %s',
	    $model->getTableName(),
	    $model->getHash()
	) === false ? false : true;
    }


    /**
     * Storage processing
     * @return string
     */
    public function process()
    {
	#Debug::consoleDump($this->renamedFields,'renamed fields');
	#Debug::consoleDump($this->renamedModels,'renamed models');
	#Debug::consoleDump($this->queue,'queue');

	# renamed fields, remove adds and drops
	foreach( $this->renamedFields as $key => $array)
	{
	    if ( $array->counter == 2 and isset($array->from) and isset($array->to) and isset($array->modelName))
	    {
		$field= $this->queue[PerfORMStorage::FIELD_ADD][$array->to.'|'.$array->modelName]->values['field'];
		unset($this->queue[PerfORMStorage::FIELD_ADD][$array->to.'|'.$array->modelName]);
		unset($this->queue[PerfORMStorage::FIELD_DROP][$array->from.'|'.$array->modelName]);

		$this->query('update [fields] set [name] = %s where [name] = %s and [table] = %s',
		$array->to,
		$array->from,
		$array->modelName);

		$this->query('update [tables] set [hash] = %s where [name] = %s',
		$field->getModel()->getHash(),
		$array->modelName);

		PerfORMController::getBuilder()->renameField($field, $array->from);
	    }
	}

	# renamed tables, remove adds and drops
	foreach( $this->renamedModels as $key => $array)
	{
	    if ( $array->counter == 2 and isset($array->from) and isset($array->to))
	    {
		$model= $this->queue[PerfORMStorage::TABLE_ADD][$array->to]->values['model'];
		unset($this->queue[PerfORMStorage::TABLE_ADD][$array->to]);
		unset($this->queue[PerfORMStorage::TABLE_DROP][$array->from]);

		$this->query('update [tables] set [name] = %s where [name] = %s',
		$array->to,
		$array->from);

		$this->query('update [fields] set [table] = %s where [table] = %s',
		$array->to,
		$array->from);

		PerfORMController::getBuilder()->renameTable($model, $array->from);
	    }
	}

	foreach( $this->queue as $operation => $actions)
	{
	    foreach($actions as $key => $action)
	    {
		//list($field, $table)= explode('|', $key);

		switch($operation)
		{
		    case PerfORMStorage::FIELD_ADD:
			$field= $action->values['field'];

			$builder= PerfORMController::getBuilder('fieldadd');
			$template= $builder->getTemplate('field-add');
			$fieldInfo= $builder->getField($field);
			$fieldInfo->nullable= true;
			$template->field= $fieldInfo;

			PerfORMController::getBuilder()->addToBuffer( $builder->renderTemplate($template) );
			//PerfORMController::getConnection()->nativeQuery($sql);

			$result= PerfORMController::getConnection()->query('select * from %n',
			    $field->getModel()->getTableName()
			);

			$pk= $field->getModel()->getPrimaryKey();
			foreach($result as $row )
			{
			    if ( !is_null($default= $field->getDefault($row)))
			    {
				PerfORMController::getBuilder()->addToBuffer(
				    PerfORMController::getConnection()->sql('update %n set %n = %'.$field->getType().' where %n = %i;',
					$field->getModel()->getTableName(),
					$field->getName(),
					$default,
					$pk,
					$row->{$pk}
				    ));
			    }
			    elseif ( !$field->isNullable())
			    {
				throw new Exception("Unable to find default value for field '".$field->getName()."' (id=".$row->{$pk}.")");
			    }
			}

			if (!$field->isNullable())
			{
			    $fieldInfo->nullable= false;
			    $template= $builder->getTemplate('field-change-nullable');
			    $template->field= $fieldInfo;
			    PerfORMController::getBuilder()->addToBuffer( $builder->renderTemplate($template) );
			    //PerfORMController::getConnection()->nativeQuery($sql);
			}

			//PerfORMController::getBuilder()->addField();
			$this->updateModelSync($action->values['field']->getModel());
			break;

		    case PerfORMStorage::FIELD_DROP:
			PerfORMController::getBuilder()->dropField($action->values['fieldName'], $action->values['model']);
			$this->updateModelSync($action->values['model']);
			break;

		    case PerfORMStorage::TABLE_ADD:
			PerfORMController::getBuilder()->createTable($action->values['model']);
			//$this->updateModelSync();
			break;

		    case PerfORMStorage::TABLE_DROP:
			PerfORMController::getBuilder()->dropTable($action->values['model']);
			break;
		}

		if ( is_array($action->sql))
		{
		    array_walk($action->sql, array($this, 'query'));
		}
		else
		{
		    $this->query($action->sql);
		}
	    }
	}

	return PerfORMController::getBuilder()->getSql();
    }


    /**
     * Adds item to queue
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
     * Update field in storage, sets hashes for field and it's model
     * @param Field $field
     */
    public function updateFieldSync($field)
    {
	$this->query('update [fields] set [hash] = %s where [name] = %s and [table] = %s',
	$field->getHash(),
	$field->getName(),
	$field->getModel()->GetTableName()
	);

	$this->updateModelSync($field->getModel());
    }


    /**
     * Update model in storage, sets hashes for it's model
     * @param PerfORM $model
     */
    public function updateModelSync($model)
    {
	$this->query('update [tables] set [hash] = %s where [name] = %s',
	$model->getHash(),
	$model->getTableName()
	);
    }
}
