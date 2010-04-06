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
 * DateTimeField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class DateTimeField extends Field {


    /**
     * AutoNow switch
     * @var boolean
     */
    protected $autoNow= false;


    /**
     * AutoNowAdd switch
     * @var boolean
     */
    protected $autoNowAdd= false;


    /**
     * strftime's format used to format value when getting
     * @var string
     */
    protected $outputFormat= 'd.m.Y H:i:s';


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_DATETIME;


    /**
     * Enables auto_now functiononality
     * @return this
     */
    public function enableAutoNow()
    {
	if ( $this->isFrozen()) throw new FreezeException();
	$this->autoNow= true;
	return $this;
    }


    /**
     * Enables auto_now_add functiononality
     * @return this
     */
    public function enableAutoNowAdd()
    {
	if ( $this->isFrozen()) throw new FreezeException();
	$this->autoNowAdd= true;
	return $this;
    }


    /**
     * Getter for field's value
     * @param boolean $insert true is insert, false is update
     * @return mixed
     */
    public function getDbValue($insert)
    {
	if ( !$this->isModified() &&
	    (
		(($this->isAutoNow() || $this->isAutoNowAdd()) && $insert) ||
		($this->isAutoNow() && !$insert)
	    )
	)
	{
	    $this->now();
	    $this->modified= true;
	}
	return parent::getDbValue($insert);
    }


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::DateTimeField;
    }


    /**
     * Getter for ModelCacheBuilder, sets phpdoc type for property-write tag in model cache
     * @return string
     */
    static public function getPhpDocProperty()
    {
	return 'string';
    }

    
    /**
     * Getter for field's value
     * @return mixed
     */
    public function getValue()
    {
	return !is_null(parent::getValue()) ? date($this->outputFormat,parent::getValue()) : null;
    }


    /**
     * Determined whether auto_now feature is enabled
     * @return boolean
     */
    public function isAutoNow()
    {
	return $this->autoNow;
    }


    /**
     * Determined whether auto_now_add feature is enabled
     * @return boolean
     */
    public function isAutoNowAdd()
    {
	return $this->autoNowAdd;
    }


    /**
     *
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value)
    {
	$retyped= $this->retype($value);
	return ( is_null($retyped)) or is_integer($retyped) or is_object($retyped) ? true : false;
    }


    /**
     * Setts value of input to current timestamp
     */
    public function now()
    {
	$this->value= time();
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    public function retype($value)
    {
	if ( is_null($value))
	{
	    return null;
	}
	elseif (preg_match('#^[0-9]+$#', $value))
	{
	    return (int) $value;
	}
	elseif( is_string($value))
	{
	    $dt= new DateTime($value);
	    return $dt->format('U');
	}
    }

    /**
     * Setter for output format
     * @see http://sk.php.net/manual/en/function.date.php
     * @param string $format
     */
    public function setOutputFormat($format)
    {
	if ( $this->isFrozen()) throw new FreezeException();
	$this->outputFormat= $format;
    }


    /**
     * Magic function, formats timestamp value to string
     * @return string
     */
    public function  __toString()
    {
	$value= $this->getValue();
	return is_null($value) ? '' : $value;
    }
}
