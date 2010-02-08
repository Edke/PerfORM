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
 * IntegerField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

class IntegerField extends Field
{


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_INTEGER;


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
	return 'integer';
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    final public function retype($value)
    {
	return (int) $value;
    }
}
