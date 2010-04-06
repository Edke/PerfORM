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
 * DecimalField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class DecimalField extends Field
{


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_FLOAT;


    /**
     * Max digits
     * @var integer
     */
    protected $digits;


    /**
     * Total decimal places
     * @var integer
     */
    protected $decimals;


    /**
     * Constructor, parses charfield specific options
     * @var string $name
     * @var integer $maxDigits
     * @var integer $decimalPlaces
     */
    public function  __construct($name, $maxDigits, $decimalPlaces)
    {
	parent::__construct($name);
	$this->setDigits($maxDigits);
	$this->setDecimals($decimalPlaces);
    }


    /**
     * Getter for decimal places
     */
    final public function getDecimals()
    {
	return $this->decimals;
    }


    /**
     * Getter for max digits
     */
    final public function getDigits()
    {
	return $this->digits;
    }


    /**
     * Getter for hash, uses field's hash and adds max_digits and decimal_places as additional parameters for hashing
     * @return string
     */
    public function getHash()
    {
	if ( !$this->hash )
	{
	    $this->hash= md5($this->getDecimals().'|'. $this->getDigits().'|'.parent::getHash());
	}
	return $this->hash;
    }


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::DecimalField;
    }


    /**
     * Getter for ModelCacheBuilder, sets phpdoc type for property-write tag in model cache
     * @return string
     */
    static public function getPhpDocProperty()
    {
	return 'integer';
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    public function retype($value)
    {
	return (float) $value;
    }


    /**
     * Setter for decimal places
     * @param integer $decimals
     */
    protected function setDecimals($decimals)
    {
	$this->decimals= (int) $decimals;
    }


    /**
     * Checks if value is valid for field
     * @param <type> $value
     * @return <type>
     */
    protected function isValidValue($value)
    {
	//Debug::barDump($this->getRegex());
	return (preg_match($this->getRegex(), $value)) ? true : false;
    }


    /**
     * Regular expression to test default value
     * @return string
     */
    protected function getRegex()
    {
	if ( $this->digits == $this->decimals )
	{
	    return '#^(0[,.]+\d*|[,.]+\d?)$#';
	}
	elseif ( $this->digits > $this->decimals )
	{
	    $n= $this->digits-$this->decimals;
	    return '#^(\d{1,'.$n.'}[,.]?|\d{1,'.$n.'}[,.]+\d*)$#';
	}
    }


    /**
     * Setter for maximal digits
     * @param integer $digits
     */
    protected function setDigits($digits)
    {
	$this->digits= (int) $digits;
    }


    /**
     * Validates field's errors and returns them as array
     * @return array
     */
    public function validate() {
	if ( is_null($this->decimals)) {
	    $this->_addError("required option decimal_places was not set");
	}
	if ( is_null($this->digits)) {
	    $this->_addError("required option max_digits was not set");
	}

	if ( !is_null($this->decimals) and
	    !is_null($this->digits) and
	    ($this->digits < $this->decimals) )
	{
	    $this->_addError("max_digits has to be egual or bigger than decimal_places");
	}

	return parent::validate();
    }
}
