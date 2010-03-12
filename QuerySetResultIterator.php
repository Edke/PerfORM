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
 * QuerySetResultIterator
 *
 * ....
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

final class QuerySetResultIterator implements Iterator, Countable
{

    /**
     * @var QuerySetResult
     */
    protected $result;

    /** @var int */
    protected $offset;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $row;

    /** @var int */
    protected $pointer;

    
    public function __construct(QuerySetResult $result, $offset = NULL, $limit = NULL)
    {
	$this->result= $result;
	$this->offset = (int) $offset;
	$this->limit = $limit === NULL ? -1 : (int) $limit;
    }



    /**
     * Rewinds the iterator to the first element.
     * @return void
     */
    public function rewind()
    {
	$this->pointer = 0;
	$this->result->seek($this->offset);
	$this->row = $this->result->fetch();
    }



    /**
     * Returns the key of the current element.
     * @return mixed
     */
    public function key()
    {
	    return $this->pointer;
    }



    /**
     * Returns the current element.
     * @return mixed
     */
    public function current()
    {
	$modelName= $this->result->getModelName();
	$model = new $modelName;
	$model->fill($this->row);
	return $model;
    }



    /**
     * Moves forward to next element.
     * @return void
     */
    public function next()
    {
	$this->row = $this->result->fetch();
	$this->pointer++;
    }



    /**
     * Checks if there is a current element after calls to rewind() or next().
     * @return bool
     */
    public function valid()
    {
	    return !empty($this->row) && ($this->limit < 0 || $this->pointer < $this->limit);
    }
    

    final public function count()
    {
	return $this->result->count();
    }
}
