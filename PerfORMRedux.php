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
 * PerfORMRedux
 *
 * Simplifies PerfORM object for easy and fast data manipulation
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */
class PerfORMRedux extends Object {

    protected $fields, $rows;


    public function __construct($model, $rows = null) {
        $this->rows = $rows;
        $this->fields = $this->rebuild($model);
        unset($this->rows);
    }


    protected function rebuild($model) {

        $result = new stdClass();
        foreach ($model->getFields() as $field) {
            $name = $field->getName();
            $alias = $field->getModel()->getAlias();

            if ($field->getIdent() == PerfORM::ForeignKeyField) {
                $value = $this->rebuild($field->getReference());
            }
            elseif ($field->getIdent() == PerfORM::CharField
                    && $field->getChoices()) {

                if (is_null($this->rows)) {
                    $_value= $field->getDbValue(true);
                }
                else {
                    $_value= $this->getValue($field, $alias . '__' . $name);
                }
                $field->setValue( $_value );
                $value = new CharFieldSimplified($field);
            }
            else {
                $value = $this->getValue($field, $alias . '__' . $name);
            }
            $result->$name = $value;
        }
        return $result;
    }


    protected function getValue($field, $key) {
        if (is_null($this->rows)) {
            return $field->getValue();
        }
        elseif (key_exists($key, $this->rows)) {
            return $this->rows[$key];
        }
        else {
            return null;
            //Debug::barDump($this->rows);
            //throw new Exception("Problem with key '$key'.");
        }
    }


    public function &__get($name) {
        return $this->fields->{$name};
    }


    public function __sleep() {
        return array('fields');
    }
}

