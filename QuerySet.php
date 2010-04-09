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
	if ( $model->isExtended())
	{
	    $this->addFields($model->getExtend());
	}
/*	    $extended= $model->getExtend();
	    foreach( $extended->getFields() as $field )
	    {
		if ( !$field->isPrimaryKey())
		{
		    $this->fields[]= sprintf("\t%s.%s as %s__%s", $extended->getAlias(), $field->getRealName(), $field->getModel()->getAlias(), $field->getRealName() );
		    if ( $field->getIdent() == PerfORM::ForeignKeyField &&
			!$field->isEnabledLazyLoading()
		    )
		    {
			$this->addFields($field->getReference());
		    }
		}
	    }
	}*/

	foreach( $model->getFields() as $field )
	{
	    $this->fields[]= sprintf("\t\"%s\".\"%s\" as \"%s__%s\"", $model->getAlias(), $field->getRealName(), $model->getAlias(), $field->getRealName() );
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
	if ( $model->isExtended())
	{
	    $this->joins[]= sprintf("\tINNER JOIN \"%s\" AS \"%s\" ON \"%s\".\"%s\" = \"%s\".\"%s\"",
		    $model->getExtend()->getTableName(),
		    $model->getExtend()->getAlias(),
		    $model->getExtend()->getAlias(),
		    $model->getExtend()->getPrimaryKey(),
		    $model->getAlias(),
		    $model->getField($model->getPrimaryKey())->getRealName()
		);
	}

	foreach( $model->getFields() as $field )
	{
	    if ( $field->getIdent() == PerfORM::ForeignKeyField &&
		!$field->isEnabledLazyLoading()
	    )
	    {
		$join_type= !$field->isNullable() && $inner ? 'INNER' : 'LEFT' ;
		
		$this->joins[]= sprintf("\t%s JOIN \"%s\" AS \"%s\" ON \"%s\".\"%s\" = \"%s\".\"%s\"",
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
     * Deletes current datasource, including inheritated models
     */
    public function delete()
    {
	$pk= $this->model->getPrimaryKey();
	$result= $this->select($pk, 'id')
	    ->getDataSource();
	$tablenames= $this->model->getAllTableNames();
	foreach($result as $row)
	{
	    foreach( $tablenames as $tablename)
	    {
		PerfORMController::getConnection()->query("DELETE FROM %n", $tablename, 'where %n = %i', $pk, $row->{$pk} );
		PerfORMController::addSql(dibi::$sql);
	    }
	}
    }

    /**
     * Limits number of rows.
     * @param  int limit
     * @param  int offset
     * @return DibiDataSource  provides a fluent interface
     */
    public function applyLimit($limit, $offset = null)
    {
	$this->getDataSource()->applyLimit($limit, $offset);
	return $this;
    }


    /**
     * Selects columns to order by.
     * @param  string|array column name or array of column names
     * @param  string          sorting direction
     * @return DibiDataSource  provides a fluent interface
     *
     */
    public function orderBy($sourceField, $sorting = 'ASC')
    {
	$field= $this->fieldLookup($sourceField);
	$row= $field->getModel()->getAlias() .'__'. $field->getRealName();
	$this->getDataSource()->orderBy($row, $sorting);
	return $this;
    }


    /**
     * Selects columns to query.
     * @param  string|array column name or array of column names
     * @param  string           column alias
     * @return DibiDataSource  provides a fluent interface
     */
    public function select($sourceField, $as = NULL)
    {
	$field= $this->fieldLookup($sourceField);
	$row= $field->getModel()->getAlias() .'__'. $field->getRealName();
	$this->getDataSource()->select($row, $as);
	return $this;
    }


    /**
     * Finds field object from relation notation
     * @param string $sourceField
     * @return Field
     */
    protected function fieldLookup($sourceField)
    {
	if ( preg_match("#^(id|pk)$#i", $sourceField, $_match) )
	{
	    if ( $this->model->getPrimaryKey() or $this->model->hasField('id') )
	    {
		return $_match[1] == 'pk' ? $this->model->getField($this->getModel()->getPrimaryKey()) : $this->model->getField($_match[1]);
	    }
	    else
	    {
		throw  new Exception ('Model does not have primary key nor id field');
	    }
	}
	elseif ( preg_match("#^[a-z0-9]+(__([a-z0-9_]+))+$#i", $sourceField, $_match))
	{
	    return $this->getModel()->pathLookup($sourceField);
	}
	elseif( !preg_match("#__#", $sourceField) && $this->getModel()->hasField($sourceField))
	{
	    return $this->getModel()->getField($sourceField, true);
	}
	else
	{
	    throw new Exception("Unable to translate field '$sourceField'.");
	}
    }


    public function where()
    {
	$args= func_get_args();
	$cond= PerfORMController::getConnection()->sql($args);
	$sql_operators=
	    '<|>|<=|>=|=|<>|!=|'.
	    '\|\||'.
	    '~|~~|~\*|!~|!~\*';
	$search= array();
	$replace= array();
	$fields= array();
	
	if ( preg_match_all('#[\"]?([a-z0-9_]+)[\"]?\s*('.$sql_operators.')#i', $cond, $matches) )
	{
	    foreach($matches[1] as $key => $originalFieldName)
	    {
		$search[]= $matches[0][$key];
		$field= $this->fieldLookup($originalFieldName);
		$replace[]= '%n '.$matches[2][$key];
		$fields[]= $field->getModel()->getAlias().'__'.$field->getRealName();
	    }
	}
	else
	{
	    throw new Exception('Unable to match operator.');
	}
	#Debug::barDump($search,'search');
	#Debug::barDump($replace,'replace');
	#Debug::barDump($fields, 'fields');
	$finalCond= str_replace($search, $replace, $cond );
	#Debug::barDump($finalCond, 'final condition');
	#Debug::barDump($fields);
	$args= array_merge( array($finalCond), $fields);
	$this->getDataSource()->where($args);
	return $this;
    }


    /**
     * Get method to retreive single result and fill model
     * @param mixed
     * @return DibiResult
     */
    public function load()
    {
	if ( $args = func_get_args() ) $this->where($args);
	$result= $this->getDataSource()->fetch();
	if ( Environment::getConfig('perform')->profiler ) PerfORMController::addSql(dibi::$sql);
	$this->getModel()->fill($result);
	return $result ? $this->getModel() : false;
    }


    public function get()
    {
	if ( $args = func_get_args() ) $this->where($args);
	return new QuerySetResult($this->getDataSource()->getResult(), $this->getModelName());
    }

    /**
     * Returns array of simplified models of current result
     * @return array
     */
    public function getSimplified()
    {
	$result= $this->get();
	$simplifiedModels= array();
	foreach($result as $model)
	{
	    $simplifiedModels[]= $model->simplify();
	}
	return $simplifiedModels;
    }


    protected function getModelName()
    {
	return get_class($this->model);
    }


    /**
     * Getter for QuerySet's model
     * @return PerfORM
     */
    protected function getModel()
    {
	return $this->model;
    }


    /**
     * Getter for datasource
     * @return DibiDataSource
     */
    public function getDataSource()
    {
	if (!$this->dataSource)
	{
	    # build query
	    $query= array();
	    $query[]= "\nSELECT";
	    $this->addFields($this->model);
	    $query[]= implode(",\n",$this->fields);
	    $query[]= sprintf("FROM \"%s\"", $this->model->getTableName() );
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
	    $model->fill($values);
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
