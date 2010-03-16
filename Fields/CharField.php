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
    public function  __construct()
    {
	$args= func_get_args();
	$args= ( is_array($args) && count($args) == 1 && isset($args[0]) ) ? $args[0] : $args;
	$options= parent::__construct($args);

	foreach ( $options as $option){
	    if ( preg_match('#^max_length=([0-9]+)$#i', $option, $matches) ) {
		$this->setSize( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( preg_match('#^choices=(.+)$#i', $option, $matches) ) {
		$this->setChoices( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( __CLASS__ == get_class($this))  {
		$this->addError("unknown option '$option'");
	    }
	}
	return $options;
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
    protected function  setChoices($choices){

	if ( !method_exists($this->getModel(), $choices)) {
	    $this->addError("invalid choices type");
	}
	$this->choices= call_user_func(array($this->getModel(), $choices));
    }


    /**
     * Sets size of varchar
     * @param integer $size 
     */
    protected function  setSize($size){
	$size= (int) $size;
	if ( !$size>0) {
	    $this->addError("invalid size '$size'");
	}
	$this->size= $size;
    }


    /**
     * Validates field's errors and returns them as array
     * @return array
     */
    public function validate() {
	if ( is_null($this->size)) {
	    $this->addError("required option max_length was not set");
	}
	return parent::validate();
    }


    public function __toString()
    {
	return parent::getValue();
    }
}
