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
 * IntegerField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class IntegerField extends Field
{


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_INTEGER;


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::IntegerField;
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
     * Checks if value is valid for field
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value)
    {
	return ( (string) $value == (string) $this->retype($value) ) ? true : false;
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    public function retype($value)
    {
	return (is_null($value)) ? null : (int) $value;
    }
}
