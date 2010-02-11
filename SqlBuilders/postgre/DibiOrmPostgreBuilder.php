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
 * DibiOrmPostgreBuilder
 *
 * Subclass of DibiOrmSqlBulder, builds PostgreSQL code
 *
 * @final
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

final class DibiOrmPostgreBuilder extends DibiOrmSqlBuilder
{

    CONST DRIVER = 'postgre';

    final protected function getDriver()
    {
	return self::DRIVER;
    }

    protected function translateType($field)
    {
	$fieldClass= get_class($field);

	switch ($fieldClass)
	{
	    case 'AutoField':
		return 'serial';

	    case 'IntegerField':
	    case 'ForeignKeyField':
		return 'integer';

	    case 'SmallIntegerField':
		return 'smallint';

	    case 'DecimalField':
		return sprintf('numeric(%d,%d)', $field->getDigits(), $field->getDecimals());

	    case 'CharField':
		return sprintf('character varying(%d)', $field->getSize());

	    case 'TextField':
		return 'text';

	    case 'BooleanField':
		return 'boolean';

	    default:
		throw new Exception("datatype for class '$fieldClass' not recognized by translator");

	}
    }


    public function hasNativeType($field, $nativeType)
    {
	$fieldClass= get_class($field);
	switch ($fieldClass)
	{
	    case 'AutoField':
	    case 'IntegerField':
	    case 'ForeignKeyField':
		return preg_match('#(INT4)#i', $nativeType) ? true : false;

	    case 'SmallIntegerField':
		return preg_match('#(INT2)#i', $nativeType) ? true : false;

	    case 'CharField':
		return preg_match('#(VARCHAR)#i', $nativeType) ? true : false;

	    case 'TextField':
		return preg_match('#(TEXT)#i', $nativeType) ? true : false;

	    case 'BooleanField':
		return preg_match('#(BOOL)#i', $nativeType) ? true : false;

	    case 'DecimalField':
		return preg_match('#(NUMERIC)#i', $nativeType) ? true : false;

	    default:
		throw new Exception("datatype for class '$fieldClass' not recognized by native type comparer");

	}
    }


    public function translateDefault($field)
    {

	if ( is_null($field->getDefaultValue()))
	{
	    return '';
	}

	switch ($field->getType() )
	{
	    case Dibi::FIELD_TEXT:
		return sprintf("'%s'::character varying", $field->getDefaultValue());

	    case Dibi::FIELD_INTEGER:
		return sprintf('%s', $field->getDefaultValue());

	    case Dibi::FIELD_FLOAT:
		return sprintf('%s', $field->getDefaultValue());

	    case Dibi::FIELD_BOOL:
		return sprintf('%s', ($field->getDefaultValue()) ? 'true' : 'false');

	    default:
		throw new Exception("default for class '$fieldClass' not recognized by translator");

	}
    }

    /**
     * @param DibiOrm $model
     * @return stdClass
     */
    public function getCreateTable($model)
    {
	$fields= array();
	foreach($model->getFields() as $field)
	{
	    $fields[]= $this->getField($field, $model);
	}

	$keys= array();

	if ( $pk = $model->getPrimaryKey() )
	{
	    $keys[]= $this->getPrimaryKey($model, $pk);
	}
	foreach( $model->getForeignKeys() as $foreignKey)
	{
	    $keys[]= $this->getForeignKey($model, $foreignKey);
	}

	return (object) array(
	    'table' => $model->getTableName(),
	    'fields' => $fields,
	    'keys' => $keys
	);
    }
    

    /**
     * @param DibiOrm $model
     * @param string $from
     * @return stdClass
     */
    public function getRenameTable($model, $from)
    {
	$renameSequences= array();
	$renameIndexes= array();
	if ( $pk = $model->getPrimaryKey() )
	{
	    $renameIndexes[]= $this->getPrimaryKey($model, $pk, $from);

	    if (get_class($model->getField($pk)) == 'AutoField')
	    {
		$renameSequences[]= (object) array(
		    'from' => $from.'_'.$pk.'_seq',
		    'to' => $model->getTableName().'_'.$pk.'_seq',
		);
	    }
	}

	$dropKeys= array();
	foreach( $model->getForeignKeys() as $foreignKey)
	{
	    $dropKeys[]= $this->getForeignKey($model, $foreignKey, $from);
	}

	$addKeys= array();
	foreach( $model->getForeignKeys() as $foreignKey)
	{
	    $addKeys[]= $this->getForeignKey($model, $foreignKey);
	}

	return (object) array(
	    'table' => $model->getTableName(),
	    'from' => $from,
	    'dropKeys' => $dropKeys,
	    'addKeys' => $addKeys,
	    'renameSequences' => $renameSequences,
	    'renameIndexes' => $renameIndexes
	);
    }



    public function getField($field, $renameFrom = null)
    {
	return (object) array(
	'table' => $field->getModel()->getTableName(),
	'name' => $field->getRealName(),
	'type' => $this->translateType($field),
	'nullable' => $field->isNullable(),
	'default' => $this->translateDefault($field),
	'from' => $renameFrom
	);
    }

    protected function getDropField($fieldName, $model)
    {
	return (object) array(
	'table' => $model->getTableName(),
	'name' => $fieldName,
	);
    }


    protected function getPrimaryKey($model, $pk, $from = null)
    {
	return (object) array(
	'table' => $model->getTableName(),
	'type' => 'primary',
	'constraint_name' => $model->getTableName() .'_pkey',
	'field' => $pk,
	'from_constraint_name' => $from .'_pkey',
	);
    }

    protected function getForeignKey($model, $key, $from = null)
    {
	return (object) array(
	'table' => $model->getTableName(),
	'type' => 'foreign',
	'constraint_name' => $model->getTableName() .'_'. $key->getRealName() .'_fkey',
	'key_name' => $key->getRealName(),
	'reference_table' => $key->getReferenceTableName(),
	'reference_key_name' => $key->getReferenceTableKey(),
	'from_table' => $from,
	'from_constraint_name' => $from .'_'. $key->getRealName() .'_fkey',
	);
    }
}
