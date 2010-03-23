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
 * CharFieldSimplified
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class CharFieldSimplified extends FieldSimplified {


    /**
     * Storage for choices
     * @var string
     */
    protected $choices;


    /**
     * Constructor, sets field from CharField
     */
    public function  __construct($charfield)
    {
	$this->choices= $charfield->getChoices();
	$this->value= (string) $charfield;
    }


    public function display()
    {
	if ( is_null($this->value) or !isset($this->choices))
	{
	    return $this->getValue();
	}
	else
	{
	    return $this->choices[$this->value];
	}
    }


    public function getChoices()
    {
	if ( isset($this->choices))
	{
	    return $this->choices;
	}
	return false;
    }


    /**
     * Getter for field's value
     * @return mixed
     */
    public function getValue()
    {
	return $this;
    }


    public function __toString()
    {
	return $this->value;
    }
}
