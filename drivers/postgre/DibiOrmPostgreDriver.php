<?php
/**
 * Description of dibiOrmPostgre
 *
 * @author kraken
 */
class DibiOrmPostgreDriver extends DibiOrmDriver
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

	    case 'CharField':
		return sprintf('character varying(%d)', $field->getSize());

	    default:
		throw new Exception("datatype for class '$fieldClass' not recognized by translator");

	}
    }

    protected function translateDefault($field)
    {

	if ( is_null($field->getDefaultValue()))
	{
	    return '';
	}

	switch ($field->getType() )
	{
	    case Dibi::FIELD_TEXT:
		return sprintf("DEFAULT '%s'", $field->getDefaultValue());

	    case Dibi::FIELD_INTEGER:
		return sprintf('DEFAULT (%d)', $field->getDefaultValue());

	    case Dibi::FIELD_FLOAT:
		return sprintf('DEFAULT (%d)::double precision', $field->getDefaultValue());

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

	if ( $pk = $model->getPrimaryKey() )
	{
	    $this->addKeys[]= $this->getPrimaryKey($model, $pk);
	}
	foreach( $model->getForeignKeys() as $foreignKey)
	{
	    $this->addKeys[]= $this->getForeignKey($model, $foreignKey);
	}

	return (object) array(
	    'table' => $model->getTableName(),
	    'fields' => $fields,
	);
    }

    protected function getField($field, $model)
    {
	return (object) array(
	'table' => $model->getTableName(),
	'name' => $field->getRealName(),
	'type' => $this->translateType($field),
	'notnull' => ($field->isNotNull()) ? 'NULL' : 'NOT NULL',
	'default' => $this->translateDefault($field),
	);
    }

    protected function getDropField($field, $model)
    {
	return (object) array(
	'table' => $model->getTableName(),
	'name' => $field,
	);
    }


    protected function getPrimaryKey($model, $pk)
    {
	return (object) array(
	'table' => $model->getTableName(),
	'type' => 'primary',
	'constraint_name' => $model->getTableName() .'_pkey',
	'field' => $pk
	);
    }

    protected function getForeignKey($model, $key)
    {
	return (object) array(
	'table' => $model->getTableName(),
	'type' => 'foreign',
	'constraint_name' => $model->getTableName() .'_'. $key->getRealName() .'_fkey',
	'key_name' => $key->getRealName(),
	'reference_table' => $key->getReferenceTableName(),
	'reference_key_name' => $key->getReferenceTableKey(),
	);
    }

    /**
     * @param Orm $orm
     */
    public function syncTable($orm)
    {
	$tableInfo= $orm->getConnection()->getDatabaseInfo()->getTable($orm->getTableName());
	$sql= null;

	// checking model against db
	foreach ($orm->getFields() as $name => $field)
	{

	    // column exists
	    if ( $tableInfo->hasColumn($field->getRealName()) )
	    {
		//TODO

	    }
	    else
	    {
		$template= $this->getTemplate('alter-table-add-column.psql' );
		$template->table= $orm;
		$template->field= $this->addField($field);
		$sql .= $this->renderTemplate($template);
	    }
	}

	// checking db against model
	foreach ( $tableInfo->getColumnNames() as $column)
	{

	    // column doesn't exists, needs to be dropped
	    if ( !$orm->hasField($column) )
	    {
		$template= $this->getTemplate('alter-table-drop-column.psql' );
		$template->table= $orm;
		$template->field= $column;
		$sql .= $this->renderTemplate($template);
	    }
	}


	return $sql;
    }
}
