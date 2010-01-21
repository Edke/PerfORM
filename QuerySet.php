<?php

/**
 * DibiOrm - Object-relational mapping based on David Grudl's dibi
 *
 * @copyright  Copyright (c) 2010 Eduard 'edke' Kracmar
 * @license    no license set at this point
 * @link       http://dibiorm.local :-)
 * @category   QuerySet
 * @package    DibiOrm
 */


/**
 * QuerySets
 *
 * Querying class responsible for getting data from database with help of models definition
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
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
     * @var DibiOrm
     */
    protected $model;


    /**
     * Helper to build aliases for model
     * @param array
     */
    protected $aliases;


    /**
     * Constructor
     *
     * Creates select and datasource from models definition
     * @param DibiOrm $model
     */
    public function  __construct(DibiOrm $model)
    {
	$this->model= clone $model;

	# aliases for model
	$tableName= $this->model->getTableName();
	$this->aliases[$tableName]= 2;
	$this->model->setAlias($tableName);
	$this->buildAliases($this->model);

	#Debug::consoleDump($this->model, 'aliased model');

	# build query
	$query= array();
	$query[]= 'SELECT';
	$this->addFields($this->model);
	$query[]= implode(",\n",$this->fields);
	$query[]= sprintf("FROM %s", $this->model->getTableName() );
	$this->addJoins($this->model);
	$query[]= implode("\n",$this->joins);
	$sql= implode("\n", $query);
	$this->dataSource= new DibiDataSource($sql, DibiOrmController::getConnection());

	$result= $this->dataSource->fetch();
	Debug::consoleDump($result, 'QuerySet result');
	#Debug::consoleDump(count($this->dataSource));
	#Debug::consoleDump($query, 'query');
    }


    /**
     * Adds fields from models definition
     * @param DibiOrm $model
     */
    protected function addFields($model)
    {
	foreach( $model->getFields() as $field )
	{
	    $this->fields[]= sprintf("\t%s.%s as %s__%s", $model->getAlias(), $field->getRealName(), $model->getAlias(), $field->getRealName() );
	    if ( get_class($field) == 'ForeignKeyField')
	    {
		$this->addFields($field->getReference());
	    }
	}
    }


    /**
     * Adds joins from models definition if relation to other models exists
     * @param DibiOrm $model
     */
    protected function addJoins($model)
    {
	foreach( $model->getFields() as $field )
	{
	    if ( get_class($field) == 'ForeignKeyField')
	    {
		$this->joins[]= sprintf("\tINNER JOIN %s AS %s ON %s.%s = %s.%s",
		$field->getReference()->getTableName(),
		$field->getReference()->getAlias(),
		$field->getReference()->getAlias(),
		$field->getReferenceTableKey(),
		$model->getTableName(),
		$field->getRealName()
		);
		$this->addJoins($field->getReference());
	    }
	}
    }


    /**
     * Method to get all results from model
     * @todo write method :)
     */
    public function all()
    {
    }


    /**
     * Builds recursively aliases for $model
     * @param DibiOrm $model
     */
    protected function buildAliases($model)
    {
	foreach($model->getFields() as $field)
	{
	    if ( get_class($field) == 'ForeignKeyField') {
		$foreignKeyTableName= $field->getReference()->getTableName();

		if ( key_exists($foreignKeyTableName, $this->aliases))
		{
		    $field->getReference()->setAlias($foreignKeyTableName.$this->aliases[$foreignKeyTableName]);
		    $this->aliases[$foreignKeyTableName]++;
		}
		else
		{
		    $this->aliases[$foreignKeyTableName]= 2;
		    $field->getReference()->setAlias($foreignKeyTableName);
		}
		$this->buildAliases($field->getReference());
	    }
	}
    }

    
    /**
     * Get method to retreive results
     * $param mixed
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
	    }
	    else
	    {
		throw new Exception("unknown option '$option'");
	    }
	}
	$primaryField= $this->model->getField($this->model->getPrimaryKey());

	$this->dataSource->where(
	'%n = %'.$primaryField->getType(),
	$this->model->getTableName().'__'.$primaryField->getRealName(),
	$primaryKeyValue
	);

	$this->dataSource->fetch();

	DibiOrmController::addSql(dibi::$sql);
    }
}
