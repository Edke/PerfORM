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
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

class CharField extends TextField {


    /**
     * Constructor, parses charfield specific options
     */
    public function  __construct()
    {
	$args= func_get_args();
	$args= ( is_array($args) && count($args) == 1 && isset($args[0]) ) ? $args[0] : $args;
	$options= parent::__construct($args);

	foreach ( $options as $option){
	    if ( preg_match('#^max_length=([0-9]+)$#i', $option, $matches) ) {
		$this->setSize( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( __CLASS__ == get_class($this))  {
		$this->addError("unknown option '$option'");
	    }
	}
	return $options;
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
     * Getter for size
     * @return integer
     */
    public function getSize() {
	return $this->size;
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
