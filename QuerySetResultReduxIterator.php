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
class QuerySetResultReduxIterator extends QuerySetResultIterator {

    protected $model;


    public function __construct(QuerySetResult $result, $offset = NULL, $limit = NULL) {
        parent::__construct($result, $offset, $limit);

        $modelName= $this->result->getModelName();
        $this->model= new $modelName;
    }


    public function current() {
        $resultClassName = $this->result->getResultClassName();
        $redux= new $resultClassName($this->model, $this->row);
        return $redux;
    }
}
