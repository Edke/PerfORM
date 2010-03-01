<?php

/**
 * PerfORM - Object-relational mapping based on David Grudl's dibi
 *
 * @copyright  Copyright (c) 2010 Eduard 'edke' Kracmar
 * @license    no license set at this point
 * @link       http://perform.local :-)
 * @category   QuerySet
 * @package    PerfORM
 */


/**
 * QuerySets
 *
 * Querying class responsible for getting data from database with help of models definition
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

final class QuerySet
{


    /**
     * Instance of datasource created
     * @var DibiDataSource
     */
    protected $dataSource;


    /**
     * Array of fields created for datasource
     * @var array
     */
    protected $fields= array();


    /**
     * Array of joins created for datasource
     * @var array
     */
    protected $joins= array();


    /**
     * Instance of model
     * @var PerfORM
     */
    protected $model;


    /**
     * Constructor
     * @param PerfORM $model
     */
    public function  __construct($model)
    {
	$this->model= $model;
    }


    /**
     * Adds fields recursively from model's definition
     * @param PerfORM $model
     */
    protected function addFields($model)
    {
	foreach( $model->getFields() as $field )
	{
	    $this->fields[]= sprintf("\t%s.%s as %s__%s", $model->getAlias(), $field->getRealName(), $model->getAlias(), $field->getRealName() );
	    if ( $field->getIdent() == PerfORM::ForeignKeyField &&
		!$field->isEnabledLazyLoading()
	    )
	    {
		$this->addFields($field->getReference());
	    }
	}
    }


    /**
     * Adds joins recursively from model's definition if relation to other models exists
     * @param PerfORM $model
     */
    protected function addJoins($model, $inner = true)
    {
	foreach( $model->getFields() as $field )
	{
	    if ( $field->getIdent() == PerfORM::ForeignKeyField &&
		!$field->isEnabledLazyLoading()
	    )
	    {
		$join_type= !$field->isNullable() && $inner ? 'INNER' : 'LEFT' ;
		
		$this->joins[]= sprintf("\t%s JOIN %s AS %s ON %s.%s = %s.%s",
		$join_type,
		$field->getReference()->getTableName(),
		$field->getReference()->getAlias(),
		$field->getReference()->getAlias(),
		$field->getReferenceTableKey(),
		$model->getAlias(),
		$field->getRealName()
		);
		$this->addJoins($field->getReference(), $join_type == 'INNER' );
	    }
	}
    }


    /**
     * Method to get all results from model
     * @return array|false
     */
    public function all()
    {
	return $this->prepareResult();
    }


    /**
     * Clears all model data
     */
    public function clear()
    {
	PerfORMController::getConnection()->query("DELETE FROM %n", $this->model->getTableName());
	PerfORMController::addSql(dibi::$sql);
    }


    /**
     * Fill model with values from result
     * @param PerfORM $model
     * @param array $values
     * @result PerfORM
     */
    protected function fill($model, $values)
    {
	if ($values === false) return;

	foreach($model->getFields() as $field)
	{
	    $key= $model->getAlias().'__'.$field->getRealName();

	    if ( $field->getIdent() == PerfORM::ForeignKeyField &&
		!$field->isEnabledLazyLoading()
	    )
	    {
		$child= clone $field->getReference();
		$this->fill($child, $values);
		$field->setValue($child);
	    }
	    elseif ( $field->getIdent() == PerfORM::ForeignKeyField &&
		$field->isEnabledLazyLoading()
	    )
	    {
		$field->setLazyLoadingKeyValue($values[$key]);
	    }
	    elseif ( key_exists($key, $values))
	    {
		$field->setValue($values[$key]);
	    }
	    else
	    {
		throw new Exception("The is no value in result for field '$key'");
	    }
	}
    }


    /**
     * Get method to retreive single result and fill model
     * @param mixed
     * @return DibiResult
     */
    public function get()
    {
	$options= new Set();
	$options->import(func_get_args());

	foreach ( $options as $option)
	{
	    if ( preg_match('#^(pk|id)=([0-9]+)$#i', $option, $matches) )
	    {
		$primaryKeyValue = $matches[2];
		$primaryField= $this->model->getField($this->model->getPrimaryKey());
		$this->getDataSource()->where('%n = %'.$primaryField->getType(),
		    $this->model->getAlias().'__'.$primaryField->getRealName(),
		    $primaryKeyValue);
	    }
	    elseif( preg_match('#^([^=]+)=(.+)$#i', $option, $matches) )
	    {
		if ( !$this->model->hasField($matches[1]) )
		{
		    throw new Exception("Invalid field '$matches[1]]'");
		}
		$this->getDataSource()->where('%n = %'.$this->model->getField($matches[1])->getType(),
		    $this->model->getAlias().'__'.$matches[1],
		    $matches[2]);
	    }
	    else
	    {
		throw new Exception("unknown option '$option'");
	    }
	}

	$result= $this->getDataSource()->fetch();
	PerfORMController::addSql(dibi::$sql);
	$this->fill($this->model, $result);
	$this->model->setUnmodified();
	return $result ? true : false;
    }


    /**
     * @todo actualy write method
     * @return array
     */
    public function filter($cond)
    {
	$this->getDataSource()->where(func_get_args());
	return $this->prepareResult();
    }


    /**
     * Getter for datasource
     * @return DibiDataSource
     */
    protected function getDataSource()
    {
	if (!$this->dataSource)
	{
	    # build query
	    $query= array();
	    $query[]= "\nSELECT";
	    $this->addFields($this->model);
	    $query[]= implode(",\n",$this->fields);
	    $query[]= sprintf("FROM %s", $this->model->getTableName() );
	    $this->addJoins($this->model);
	    $query[]= implode("\n",$this->joins);
	    $this->dataSource= new DibiDataSource(implode("\n", $query), PerfORMController::getConnection());
	}
	return $this->dataSource;
    }
    

    /**
     * Fetch current DataSource, fill array of models with values
     * @return array|false
     */
    protected function prepareResult()
    {
	$result= array();
	$modelName= get_class($this->model);
	foreach($this->getDataSource() as $values)
	{
	    $model= new $modelName;
	    $this->fill($model, $values);
	    $result[]= $model;
	}

	if ( sizeof($result) > 0)
	{
	    return $result;
	}
	else
	{
	    return false;
	}
    }
}
