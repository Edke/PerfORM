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
     * Constructor, parses charfield specific options
     */
    public function  __construct()
    {
	$args= func_get_args();
	$args= ( is_array($args) && count($args) == 1 && isset($args[0]) ) ? $args[0] : $args;
	$options= parent::__construct($args);
	foreach ( $options as $option){
	    if ( __CLASS__ == get_class($this))  {

		$this->addError("unknown option '$option'");
	    }
	}
	return $options;
    }

    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::DateField;
    }
}
