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
 * CharField
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

final class CharField extends Field {


    /**
     * Limit of chars in field
     * @var integer
     */
    protected $size;


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
	$options= parent::__construct(func_get_args());

	foreach ( $options as $option){
	    if ( preg_match('#^max_length=([0-9]+)$#i', $option, $matches) ) {
		$this->setSize( $matches[1]);
		$options->remove($option);
	    }
	    else{
		$this->addError("unknown option '$option'");
	    }
	}
    }


    /**
     * Getter for hash, uses field's hash and adds size as additional parameter for hashing
     * @return string
     */
    public function getHash()
    {
	if ( !$this->hash )
	{
	    $this->hash= md5($this->getSize().'|'.parent::getHash());
	}
	return $this->hash;
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
     * Getter for size
     * @return integer
     */
    public function getSize() {
	return $this->size;
    }


    /**
     * Sets field's default value
     * @param miexd $default
     */
    final public function setDefault($default)
    {
	parent::setDefault($default);

   	if ( (string) $default != (string) $this->default )
	{
	    $this->addError("invalid datatype of default value '$default'");
	    return false;
	}
    }


    /**
     * Sets size of varchar
     * @param integer $size 
     */
    protected function  setSize($size){
	$size= (int) $size;
	if ( !$size>0) {
	    throw new Exception("invalid size '$size'");
	}
	$this->size= $size;
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    final public function retype($value) {
	return (string) $value;
    }


    /**
     * Validates field's errors and returns them as array
     * @return array
     */
    public function validate() {
	if ( is_null($this->size)) {
	    $this->addError("required option max_length was not set");
	}
	return parent::validate();
    }
}
