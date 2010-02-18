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
 * SmallIntegerField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class SmallIntegerField extends IntegerField
{


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::SmallIntegerField;
    }


    /**
     * Retype value of field to string, range handling
     * @param mixed $value
     * @return string
     */
    final public function retype($value)
    {
	$integer= (int) $value;
	if ( $integer > 32767 or $integer < -32768 )
	{
	    trigger_error('Smallint value out of range', E_USER_ERROR);
	}

	return (int) $integer;
    }

}
