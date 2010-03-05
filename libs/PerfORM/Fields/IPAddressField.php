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
 * IPAddressField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class IPAddressField extends TextField {


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::IPAddessField;
    }


    /**
     *
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value)
    {
	return filter_var($value, FILTER_VALIDATE_IP) !== false;
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
	    return $value;
	}
	else {
	    throw new Exception('invalid value');
	}
    }
}
