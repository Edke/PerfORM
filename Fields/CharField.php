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
 * CharField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class CharField extends TextField {


    /**
     * Storage for choices
     * @var string
     */
    protected $choices;


    /**
     * Constructor, parses charfield specific options
     */
    public function  __construct($name, $maxLength)
    {
	parent::__construct($name);
	$this->setSize($maxLength);
    }


    /**
     * Displays human readable value of choices
     * @return mixed
     */
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


    /**
     * Getter for all choices of field
     * @return array
     */
    public function getChoices()
    {
	if ( isset($this->choices))
	{
	    return $this->choices;
	}
	return false;
    }

    /**
     * Getter for hash, uses field's hash and adds size as additional parameter for hashing
     * @return string
     */
    public function getHash()
    {
	if ( !$this->hash )
	{
	    $this->hash= md5($this->getSize().'|'.parent::getHash());
	}
	return $this->hash;
    }


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::CharField;
    }


    /**
     * Getter for size
     * @return integer
     */
    public function getSize() {
	return $this->size;
    }


    /**
     * Getter for field's value
     * @return mixed
     */
    public function getValue()
    {
	if ( isset($this->choices))
	{
	    return $this;
	}
	else
	{
	    return parent::getValue();
	}
    }


    public function setValue($value)
    {
	if ( isset($this->choices) && !key_exists($value, $this->choices) )
	{
	    throw new Exception("Invalid value '$value', does not exists in choices.");
	}
	parent::setValue($value);
    }

    /**
     * Sets choices
     * @param array $choices
     */
    public function setChoices($choices)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	if ( !method_exists($this->getModel(), $choices)) {
	    $this->_addError("invalid choices type");
	}
	$this->choices= call_user_func(array($this->getModel(), $choices));
    }


    /**
     * Sets size of varchar
     * @param integer $size 
     */
    protected function setSize($size)
    {
	$size= (int) $size;
	if ( !$size>0) {
	    $this->_addError("invalid size '$size'");
	}
	$this->size= $size;
    }


    /**
     * Simplifies field
     * @param boolean $flat
     * @return mixed
     */
    public function simplify($flat)
    {
	return $this->getChoices() && !$flat ? new CharFieldSimplified($this) : parent::getValue();
    }


    /**
     * Validates field's errors and returns them as array
     * @return array
     */
    public function validate() {
	if ( is_null($this->size)) {
	    $this->_addError("required option max_length was not set");
	}
	return parent::validate();
    }


    public function __toString()
    {
	return parent::getValue();
    }
}
