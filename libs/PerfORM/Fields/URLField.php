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
 * URLField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class URLField extends CharField {


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::URLField;
    }


    /**
     *
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value)
    {
	return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    public function retype($value) {
	if ( is_null($value) )
	{
	    return null;
	}
	elseif( $this->isValidValue($value))
	{
	    return strtolower($value);
	}
	else {
	    throw new Exception('invalid value');
	}
    }
}
