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
 * QuerySetResult
 *
 * ....
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

final class QuerySetResult implements IteratorAggregate, Countable
{

    /**
     * @var PerfORM
     */
    protected $modelName;

    /**
     * @var DibiResult
     */
    protected $result;


    public function __construct(DibiResult $result, $modelName)
    {
	$this->result= $result;
	$this->modelName= $modelName;
    }

    final public function getIterator($offset = NULL, $limit = NULL)
    {
	return new QuerySetResultIterator($this, $offset, $limit);
    }

    final public function count()
    {
	return $this->result->count();
    }


    final public function seek($row)
    {
	return $this->result->seek($row);
    }

    final public function fetch()
    {
	$result= $this->result->fetch();
	if ( $result !== false and Environment::getConfig('perform')->profiler ) PerfORMController::addSql(dibi::$sql);
	return $result;
    }

    final public function getModelName()
    {
	return $this->modelName;
    }

}
