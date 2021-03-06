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
final class ForeignKeyField extends Field {

    /**
     * Controls lazy loading of referenced object
     * @var boolean
     */
    protected $lazyLoading = false;
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
     * Mask for creating key's name
     * @var string
     */
    protected $nameMask = '%foreignKeyName_%ownName';


    /**
     * Constructor, parses foreignkey specific options
     * @var string $name
     * @var PerfORM $reference
     */
    public function __construct($model, $name, $reference) {
        $this->reference = $reference;
        parent::__construct($model, $name);
    }


    /**
     * Disables lazy loading
     */
    public function disableLazyLoading() {
        $this->lazyLoading = false;
    }


    /**
     * Enables lazy loading
     */
    public function enableLazyLoading() {
        $this->lazyLoading = true;
    }


    /**
     * Getter for hash, uses field's hash and adds isForeignKey() as additional parameter for hashing
     * @return string
     */
    public function getHash() {
        if (!$this->hash) {
            $this->hash = md5($this->isForeignKey() . '|' . parent::getHash());
        }
        return $this->hash;
    }


    /**
     * Returns identification integer of field
     * @return integer
     */
    public function getIdent() {
        return PerfORM::ForeignKeyField;
    }


    /**
     * Getter for value of key, if key has no value, issue save() for reference model
     * @param boolean $insert true is insert, false is update
     * @return mixed
     */
    public function getDbValue($insert) {
        if (!is_object($this->getValue()) && $this->isEnabledLazyLoading()) {
            return $this->getValue();
        }
        elseif (is_object($this->getValue())) {
            $value = $this->getValue()->getField($this->getReference()->getPrimaryKey())->getValue();
            if (!is_integer($value)) {
                $value = $this->getValue()->save();
            }
            return $value;
        }
        else {
            return null;
        }
    }


    /**
     * Getter for ModelCacheBuilder, sets phpdoc type for property-write tag in model cache
     * @return string
     */
    static public function getPhpDocProperty() {
        return 'integer';
    }


    /**
     * Getter for field's real name, considers it's db name, disabled temporary
     * @return string
     */
    public function getRealName() {
        if (is_null($this->dbName)) {
            $dbName = str_replace('%ownName', $this->getName(), $this->nameMask);
            $dbName = str_replace('%foreignKeyName', $this->getReference()->getPrimaryKey(), $dbName);
            $this->dbName = $dbName;
        }
        return $this->dbName;
    }


    /**
     * Getter for reference model
     * @return PerfORM
     */
    public function getReference() {
        if (is_string($this->reference)) {
            $this->reference = new $this->reference;
        }
        return $this->reference;
    }


    public function getReferenceObjectName() {
        if (is_object($this->reference)) {
            return get_class($this->getReference());
        }
        return $this->reference;
    }


    /**
     * Determines whether lazy loading is enabled
     * @return boolean
     */
    public function isEnabledLazyLoading() {
        return $this->lazyLoading;
    }


    /**
     * Determines whether field is foreign key
     * @return boolean
     */
    public function isForeignKey() {
        return true;
    }


    /**
     * Checks if value is valid for field
     * @param mixed $value
     * @return boolean
     */
    public function isValidValue($value) {
        return true;
    }


    /**
     * Retype value of field to string
     * @param mixed $value
     * @return string
     */
    final public function retype($value) {
        return $value;
    }


    /**
     * Setting directly dbName fires validation error, key's dbname is created with help of mask
     * @param string $dbName
     * @return false
     */
    public function setDbName($dbName) {
        $this->_addError("forbidden to set db_column for foreign key, change mask instead");
        return false;
    }


    /**
     * Sets key of reference table for lazy loading
     * @param integer $value
     */
    public function setLazyLoadingKeyValue($value) {
        $this->value = $value;
        $this->modified = true;
    }


    /**
     * Sets name for fields, applies mask to dbName creation
     * @param string $name
     */
    public function setName($name) {
        parent::setName($name);
        $this->dbName = null;
    }


    /**
     * Sets field's value
     * @param mixed $value
     */
    public function setValue($value) {
        if (!(is_object($value) and
                get_class($value) == $this->getReferenceObjectName())) {
            throw new Exception(get_class($this->getModel()) . '|' . $this->getName() . " - value is not valid object or model type");
        }
        $this->value = $value;
        $this->modified = true;
    }


    /**
     * Simplifies reference model
     * @param boolean $flat
     * @return stdClass
     */
    public function simplify($flat) {
        $value = $this->getValue();
        return is_object($value) ? $value->simplify($flat) : $value;
    }


    public function __sleep() {
        return array_merge(parent::__sleep(), array('lazyLoading'));
    }
}
