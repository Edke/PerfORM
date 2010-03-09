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
 * AutoField, auto-incremented integer, mostly serves as primary key
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

final class AutoField extends IntegerField {


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::AutoField;
    }
}



