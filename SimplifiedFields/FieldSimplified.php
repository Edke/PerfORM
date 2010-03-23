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
 * FieldSimplified, base model's simplified field class
 *
 * @abstract
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

abstract class FieldSimplified
{
    /**
     * Value of field
     * @var mixed
     */
    protected $value= null;


    /**
     * Constructor, sets field from CharField
     */
    public function  __construct($field)
    {
	$this->value= $field->getValue();
    }


    /**
     * Getter for field's value
     * @return mixed
     */
    public function getValue()
    {
	return $this->value;
    }


    public function __toString()
    {
	return $this->getValue();
    }
}
