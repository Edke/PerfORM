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
    protected $orm;


    /**
     * Constructor
     *
     * Creates select and datasource from models definition
     * @param DibiOrm $orm
     */
    public function  __construct(DibiOrm $orm)
    {
	$this->orm= $orm;

	$query= array();

	$query[]= 'SELECT';
	$this->addFields($orm);
	$query[]= implode(",\n",$this->fields);
	$query[]= sprintf("FROM %s", $orm->getTableName() );
	$this->addJoins($orm);
	$query[]= implode(",\n",$this->joins);

	$sql= implode("\n", $query);

	$this->dataSource= new DibiDataSource($sql, DibiOrmController::getConnection());

	$this->dataSource->fetch();
	//Debug::consoleDump(dibi::$sql, 'sql');
	//Debug::consoleDump(count($this->dataSource));
	//Debug::consoleDump($query, 'query');
    }


    /**
     * Adds fields from models definition
     * @param DibiOrm $orm
     */
    protected function addFields($orm)
    {
	foreach( $orm->getFields() as $field )
	{
	    $this->fields[]= sprintf("\t%s.%s as %s__%s", $orm->getTableName(), $field->getRealName(), $orm->getTableName(), $field->getRealName() );
	    if ( get_class($field) == 'ForeignKeyField')
	    {
		$this->addFields($field->getReference());
	    }
	}
    }


    /**
     * Adds joins from models definition if relation to other models exists
     * @param DibiOrm $orm
     */
    protected function addJoins($orm)
    {
	foreach( $orm->getFields() as $field )
	{
	    if ( get_class($field) == 'ForeignKeyField')
	    {
		$this->joins[]= sprintf("\tINNER JOIN %s ON %s.%s = %s.%s",
		$field->getReference()->getTableName(),
		$field->getReference()->getTableName(),
		$field->getReferenceTableKey(),
		$orm->getTableName(),
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
	$primaryField= $this->orm->getField($this->orm->getPrimaryKey());

	$this->dataSource->where(
	'%n = %'.$primaryField->getType(),
	$this->orm->getTableName().'__'.$primaryField->getRealName(),
	$primaryKeyValue
	);

	$this->dataSource->fetch();

	DibiOrmController::addSql(dibi::$sql);
	#Debug::consoleDump($this->dataSource->fetch(), 'fetch');
    }
}
