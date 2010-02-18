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
     */
    public function  __construct()
    {
	$args= func_get_args();
	$args= ( is_array($args) && count($args) == 1 && isset($args[0]) ) ? $args[0] : $args;
	$options= parent::__construct($args);

	foreach ( $options as $option){
	    if ( preg_match('#^max_digits=([0-9]+)$#i', $option, $matches) ) {
		$this->setDigits( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( preg_match('#^decimal_places=([0-9]+)$#i', $option, $matches) ) {
		$this->setDecimals( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( __CLASS__ == get_class($this))  {
		$this->addError("unknown option ! '$option'");
	    }
	}
	return $options;
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
    final protected function setDecimals($decimals)
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
	//Debug::consoleDump($this->getRegex());
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
    final protected function setDigits($digits)
    {
	$this->digits= (int) $digits;
    }


    /**
     * Validates field's errors and returns them as array
     * @return array
     */
    public function validate() {
	if ( is_null($this->decimals)) {
	    $this->addError("required option decimal_places was not set");
	}
	if ( is_null($this->digits)) {
	    $this->addError("required option max_digits was not set");
	}

	if ( !is_null($this->decimals) and
	    !is_null($this->digits) and
	    ($this->digits < $this->decimals) )
	{
	    $this->addError("max_digits has to be egual or bigger than decimal_places");
	}

	return parent::validate();
    }
}
