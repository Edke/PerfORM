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
 * TextField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

class TextField extends Field {


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_TEXT;


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
     * Getter for ModelCacheBuilder, sets phpdoc type for property-write tag in model cache
     * @return string
     */
    static public function getPhpDocProperty()
    {
	return 'string';
    }


    /**
     *
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
    final public function retype($value) {
	return (is_null($value)) ? null : (string) $value;
    }
}
