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
 * TimeField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class TimeField extends DateTimeField {


    /**
     * strftime's format used to format value when getting
     * @var string
     */
    protected $outputFormat= 'H:i:s';


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_TIME;


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::TimeField;
    }
}
