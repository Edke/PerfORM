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
 * AutoField, auto-incremented integer, mostly serves as primary key
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

final class AutoField extends IntegerField {


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return DibiOrm::AutoField;
    }
}



