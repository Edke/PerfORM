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
 * SlugField
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

class SlugField extends CharField {

    /**
     * Name of field to popupate slug from
     * @var string
     */
    protected $sourceField;

    /**
     * Constructor, parses charfield specific options
     */
    public function  __construct()
    {
	$args= func_get_args();
	$args= ( is_array($args) && count($args) == 1 && isset($args[0]) ) ? $args[0] : $args;
	$options= parent::__construct($args);
	foreach ( $options as $option){
	    if ( preg_match('#^auto_source=(.+)$#i', $option, $matches) ) {
		$this->setSourceField( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( __CLASS__ == get_class($this))  {
		$this->addError("unknown option '$option'");
	    }
	}
	return $options;
    }


    /**
     * Getter for field's value
     * @param boolean $insert true is insert, false is update
     * @return mixed
     */
    public function getDbValue($insert)
    {
	if ( $this->sourceField )
	{
	    $source= $this->getModel()->getField($this->sourceField);

	    if ( !$this->isModified() &&
		($insert or
		    (!$insert and $source->isModified())))
	    {
		$this->setValue($source->getValue());
		$this->modified= true;
	    }
	}
	return parent::getDbValue($insert);
    }


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::SlugField;
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    public function retype($value) {
	return is_null($value) ? null : String::webalize($value);
    }

    protected function setSourceField($field)
    {
	$this->sourceField= $field;
    }

    /**
     * Validates field's errors and returns them as array
     * @return array
     */
    public function validate() {
	if ( is_null($this->sourceField)) {
	    $this->_addError("required option auto_source was not set");
	}
	elseif( !$this->getModel()->hasField($this->sourceField))
	{
	    $this->_addError("auto_source field '$this->sourceField' does not exists");
	}
	return parent::validate();
    }
}
