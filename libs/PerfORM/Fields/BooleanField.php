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
 * BooleanField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class BooleanField extends Field
{


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_BOOL;


    /**
     * Constructor, adds error on unknown options
     */
    public function __construct()
    {
	$options= parent::__construct(func_get_args());

	foreach ( $options as $option)
	{
	    $this->addError("unknown option '$option'");
	}
    }


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::BooleanField;
    }


    /**
     * Getter for ModelCacheBuilder, sets phpdoc type for property-write tag in model cache
     * @return string
     */
    static public function getPhpDocProperty()
    {
	return 'boolean';
    }


    /**
     * Checks if value is valid for field
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value)
    {
	return preg_match('#^(t|true|y|yes|1|f|false|n|no|0)$#i', $value) ? true : false;
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
	else {
	    if ( preg_match('#^(t|true|y|yes|1)$#i', $value))
	    {
		return true;
	    }
	    elseif ( preg_match('#^(f|false|n|no|0)$#i', $value))
	    {
		return false;
	    }
	    else {
		$this->addError("unable to retype value '$value' to boolean");
	    }
	}
    }
}
