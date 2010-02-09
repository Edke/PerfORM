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
 * DibiOrmSqlBuilder
 *
 * builds sql dumps to sync models with database structure
 *
 * @abstract
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

abstract class DibiOrmSqlBuilder {


    protected $createTables= array();

    protected $dropTables= array();

    protected $dropModelTables= array();

    protected $addFields= array();

    protected $renameFields= array();

    protected $renameTables= array();

    protected $renameIndexes= array();

    protected $renameSequences= array();

    protected $dropFields= array();

    protected $dropKeys= array();

    protected $addKeys= array();

    protected $createIndexes= array();

    protected $changeFieldsToNullable= array();

    protected $changeFieldsToNotNullable= array();
    
    protected $changeFieldsDefaultValue= array();

    
    /**
     * @param Field $field
     * @param DibiOrm $model
     */
    public function appendFieldToAdd($field)
    {
	$this->addFields[]= $this->getField($field);
    }

    /**
     * @param Field $field
     * @param DibiOrm $model
     */
    public function appendFieldToRename($field, $from)
    {
	$this->renameFields[]= $this->getField($field, $from);
    }

    
    /**
     * @param DibiOrm $model
     */
    public function appendTableToRename($model, $from)
    {
	$this->renameTables[]= $this->getRenameTable($model, $from);
    }


    /**
     * @param Field $field
     * @param DibiOrm $model
     */
    public function appendFieldToDrop($fieldName, $model)
    {
	$this->dropFields[]= $this->getDropField($fieldName, $model);
    }


    /**
     * @param DibiOrm $model
     */
    public function appendTableToCreate($model)
    {
	$this->createTables[]= $model;
    }


    /**
     * @param DibiOrm|string $model
     */
    public function appendTableToDrop($model)
    {
	if ( is_object($model))
	{
	    $this->dropModelTables[]= $model;
	}
	else {
	    $this->dropTables[]= $model;
	}
    }



    public function appendFieldToNullable($field)
    {
	$this->changeFieldsToNullable[]= $this->getField($field);
    }

    
    public function appendFieldToNotNullable($field)
    {
	$this->changeFieldsToNotNullable[]= $this->getField($field);
    }

    public function appendFieldToChangeDefault($field)
    {
	$this->changeFieldsDefaultValue[]= $this->getField($field);
    }

    abstract protected function getDriver();

    abstract protected function getField($field, $renameFrom = null);

    abstract public function translateDefault($field);

    protected function getTemplate() {
	$template= new Template();
	$template->registerFilter(new LatteFilter);
	$template->setFile( dirname(__FILE__).'/'. $this->getDriver() . '/DibiOrmPostgreTemplate.psql');
	return $template;
    }

    /**
     * Renders sql template and removes unnecessary empty lines
     * @param Template $template
     * @return string
     */
    protected function renderTemplate($template) {
    	ob_start();
	$template->render();
	return trim(preg_replace('#(;([\s\n\r]+))#ms', ";\n\n", ob_get_clean()));
    }


    /**
     * Models with dependancy defined are moved before its dependents
     * @param array $list
     */
    protected function dependancySort( &$list)
    {
	$_list= $list;
	foreach($_list as $item)
	{
	    $min= null;
	    foreach($item->getDependents() as $dependent)
	    {
		$min= ( is_null($min )) ?  $this->model_array_search($dependent, $_list) : min($min, array_search($dependent, $_list)) ;
	    }
	    if (is_integer($min))
	    {
		if ( array_search($item, $_list) > $min)
		{
		    unset($list[array_search($item,$list)]);
		    $list= $this->insertArrayIndex($list, $item, $min);
		}
	    }
	}
    }

    protected function model_array_search($needle, $haystack)
    {
	foreach($haystack as $key => $value)
	{
	    if ( get_class($needle) === get_class($value)
		and $needle->getHash() === $value->getHash() )
	    {
		return $key;
	    }
	}
	return false;
    }
    

    /**
     * Reversed sort of dependancySort method
     * @param array $list
     */
    protected function dependancySortReverse( &$list)
    {
	$this->dependancySort($list);
	$list= array_reverse($list);
    }


    /**
     * Inserts element at $index of $array
     * @param array $array
     * @param mixed $new_element
     * @param integer $index
     * @return array
     */
    protected function insertArrayIndex($array, $new_element, $index)
    {
	$start = array_slice($array, 0, $index);
	$end = array_slice($array, $index);
	$start[] = $new_element;
	return array_merge($start, $end);
    }


    abstract function getCreateTable($model);
    

    /**
     * Builds sql dump
     * @return string
     */
    public function build()
    {
	$template= $this->getTemplate();

	$template->createTables= array();
	$template->dropTables= array();

	$template->addFields= $this->addFields;
	$template->dropFields= $this->dropFields;
	$template->renameFields= $this->renameFields;
	$template->renameTables= $this->renameTables;
	$template->renameSequences= $this->renameSequences;
	$template->renameIndexes= $this->renameIndexes;
	$template->changeFieldsToNullable= $this->changeFieldsToNullable;
	$template->changeFieldsToNotNullable= $this->changeFieldsToNotNullable;
	$template->changeFieldsDefaultValue= $this->changeFieldsDefaultValue;

	# create table ..
	self::dependancySortReverse($this->createTables);
	foreach($this->createTables as $model)
	{
	    $template->createTables[]= $this->getCreateTable($model);
	}

	$template->addKeys= $this->addKeys;
	$template->dropKeys= $this->dropKeys;
	$template->createIndexes= $this->createIndexes;

	# drop table ..
	self::dependancySort($this->dropModelTables);
	foreach($this->dropModelTables as $model)
	{
	    $template->dropTables[]= $model->getTableName();
	}
	foreach($this->dropTables as $table)
	{
	    $template->dropTables[]= $table;
	}

	return $this->renderTemplate($template);
    }
}

