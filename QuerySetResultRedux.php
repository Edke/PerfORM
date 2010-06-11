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
 * QuerySetResultRedux
 *
 * ....
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class QuerySetResultRedux extends QuerySetResult
{
    protected $resultClassName;

    public function __construct(DibiResult $result, $modelName, $resultClassName)
    {
	$this->result= $result;
	$this->modelName= $modelName;
        $this->resultClassName= $resultClassName;
    }

    final public function getIterator($offset = NULL, $limit = NULL)
    {
	return new QuerySetResultReduxIterator($this, $offset, $limit);
    }


    final public function getResultClassName() {
        return $this->resultClassName;
    }
}
