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
 * ForeignKeyField, sets relation via forein keys to other table
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

final class ForeignKeyField extends Field
{


    /**
     * Datatype
     * @var string
     */
    protected $type = Dibi::FIELD_INTEGER;


    /**
     * References to relation model
     * @var PerfORM
     */
    protected $reference;


    /**
     * Name of field that key references to
     * @var string
     */
    protected $referenceKey;


    /**
     * Mask for creating key's name
     * @var string
     */
    protected $nameMask= '%foreignKeyName_%ownName';


    /**
     * Constructor, parses foreignkey specific options
     */
    public function  __construct()
    {
	$options= parent::__construct(func_get_args());

	foreach ( $options as $option)
	{
	    if ( is_object($option) && is_subclass_of($option, 'PerfORM'))
	    {
		$this->reference= $option;
		$options->remove($option);
	    }
	    else
	    {
		$this->addError("unknown option '$option'");
	    }
	}
    }


    /**
     * Getter for hash, uses field's hash and adds isForeignKey() as additional parameter for hashing
     * @return string
     */
    public function getHash()
    {
	if ( !$this->hash )
	{
	    $this->hash= md5($this->isForeignKey().'|'.parent::getHash());
	}
	return $this->hash;
    }


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent()
    {
	return PerfORM::ForeignKeyField;
    }


    /**
     * Getter for value of key, if key has no value, issue save() for reference model
     * @param boolean $insert true is insert, false is update
     * @return mixed
     */
    public function getDbValue($insert)
    {
	if ( is_object($this->value) )
	{
	    $value= $this->value->getField($this->referenceKey)->getValue();
	    if ( !is_integer($value))
	    {
		$value= $this->value->save();
	    }
	    return $value;
	}
	else
	{
	    return null;
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
     * Getter for reference model
     * @return PerfORM
     */
    public function getReference()
    {
	return $this->reference;
    }


    /**
     * Get reference model's table name
     * @return string
     */
    public function getReferenceTableName()
    {
	return $this->reference->getTableName();
    }


    /**
     * Get reference model's key name
     * @return <type>
     */
    public function getReferenceTableKey()
    {
	return $this->referenceKey;
    }


    /**
     * Determines whether field is foreign key
     * @return boolean
     */
    public function isForeignKey()
    {
	return true;
    }


    /**
     * Checks if value is valid for field
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value)
    {
	return true;
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    final public function retype($value)
    {
	return $value;
    }


    /**
     * Setting directly dbName fires validation error, key's dbname is created with help of mask
     * @param string $dbName
     * @return false
     */
    protected function setDbName($dbName)
    {
	$this->addError("forbidden to set db_column for foreign key, change mask instead");
	return false;
    }


    /**
     * Sets name for fields, applies mask to dbName creation
     * @param string $name
     */
    public function setName($name)
    {
	parent::setName($name);

	$this->referenceKey= $this->reference->getPrimaryKey();
	$dbName=str_replace('%ownName', $name, $this->nameMask);
	$dbName=str_replace('%foreignKeyName', $this->referenceKey, $dbName);
	parent::setDbName($dbName);
    }


    /**
     * Sets field's value
     * @param mixed $value
     */
    public function setValue($value)
    {
	if ( !(is_object($value) and
		get_class($value) == get_class($this->getReference())) )
	{
	    throw new Exception(get_class($this->getModel()).'|'. $this->getName().  " - value is not valid object or model type");
	}
	$this->value= $value;
	$this->modified= true;
    }
}
