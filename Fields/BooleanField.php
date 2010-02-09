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
 * BooleanField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

class BooleanField extends Field
{


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_BOOL;


    /**
     * Constructor, adds error on unknown options
     */
    public function __construct()
    {
	$options= parent::__construct(func_get_args());

	foreach ( $options as $option)
	{
	    $this->addError("unknown option '$option'");
	}
    }


    /**
     * Getter for ModelCacheBuilder, sets phpdoc type for property-write tag in model cache
     * @return string
     */
    static public function getPhpDocProperty()
    {
	return 'boolean';
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    final public function retype($value)
    {
	if ( preg_match('#(t|true|y|yes|1)#i', $value))
	{
	    return true;
	}
	elseif ( preg_match('#(f|false|n|no|0)#i', $value))
	{
	    return false;
	}
	else {
	    $this->addError("unable to retype value '$value' to boolean");
	}
    }
}
