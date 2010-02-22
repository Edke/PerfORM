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
 * Index, field's index class
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class Index
{

    /**
     * Array of indexes field is in
     * @var array
     */
    protected $fields= array();


    /**
     * Hash of index for structure checking
     * @var string
     */
    protected $hash;


    /**
     * Is field mandatory ?
     * @var boolean
     */
    protected $isUnique= false;


    /**
     * Back reference to field's model
     * @var PerfORM
     */
    protected $model;


    /**
     * Name of index
     * @var string
     */
    protected $name;


    /**
     * Constructor, parses for field's options and returns unknown ones
     *
     * @param array $_options
     * @return Set
     */
    public function __construct($model, $fieldName, $indexName, $unique)
    {
	$this->model= $model;
	$this->name= $indexName;
	$this->addField($fieldName);
	$this->isUnique= $unique;
    }


    /**
     * Adds field to index field's list
     */
    public function addField($fieldName)
    {
	$this->fields[]= $fieldName;
    }


    /**
     * Getter for index's fields
     * @return arrays
     */
    public function getFields()
    {
	return $this->fields;
    }


    /**
     * Getter for field's hash
     * @return string
     */
    public function getHash()
    {
	if ( !$this->hash )
	{
	    $array= array(
	    $this->isUnique(),
	    implode('|', $this->getFields()),
	    $this->name,
	    );
	    $this->hash= md5(implode('|', $array));
	}
	return $this->hash;
    }


    /**
     * Getter for index's model
     * @return PerfORM
     */
    public function getModel()
    {
	return $this->model;
    }


    /**
     * Getter for index's name
     * @return string
     */
    public function getName()
    {
	return $this->name;
    }


    /**
     * Determines whether index is unique
     * @return boolean
     */
    public function isUnique()
    {
	return $this->isUnique;
    }


    /**
     * Cleans circular references, should prevent memory leaks
     */
    public function  __destruct()
    {
	unset($this->model);
    }
}
