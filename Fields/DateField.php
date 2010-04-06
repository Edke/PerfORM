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
 * DateField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class DateField extends DateTimeField {


    /**
     * strftime's format used to format value when getting
     * @var string
     */
    protected $outputFormat= 'd.m.Y';


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_DATE;


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::DateField;
    }
}
