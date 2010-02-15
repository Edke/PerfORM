<?php

/**
 * DibiOrm - Object-relational mapping based on David Grudl's dibi
 *
 * @copyright  Copyright (c) 2010 Eduard 'edke' Kracmar
 * @license    no license set at this point
 * @link       http://dibiorm.local :-)
 * @category   DibiOrm
 * @package    DibiOrm
 */


/**
 * DateTimeField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

class DateTimeField extends Field {

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
     * Constructor, parses charfield specific options
     */
    public function  __construct()
    {
	$args= func_get_args();
	$args= ( is_array($args) && count($args) == 1 && isset($args[0]) ) ? $args[0] : $args;
	$options= parent::__construct($args);
	foreach ( $options as $option){
	    if ( preg_match('#^output_format=(.+)$#i', $option, $matches) ) {
		$this->setOutputFormat( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( __CLASS__ == get_class($this))  {

		$this->addError("unknown option '$option'");
	    }
	}
	return $options;
    }


    /**


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return DibiOrm::DateTimeField;
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
	return date($this->outputFormat,$this->value);
    }


    /**
     *
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value)
    {
	return ( is_null($value)) or  preg_match('#^[0-9]+$#', $value) or new DateTime($value) ? true : false;
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
    final public function retype($value)
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
    protected function setOutputFormat($format)
    {
	$this->outputFormat= $format;
    }


    /**
     * Magic function, formats timestamp value to string
     * @return string
     */
    public function  __toString()
    {
	return $this->getValue();
    }
}
