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
 * PerfORMSqlBuilder
 *
 * builds sql dumps to sync models with database structure
 *
 * @abstract
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */

abstract class PerfORMSqlBuilder {

    protected $createTables= array();

    protected $dropTables= array();

    protected $dropModelTables= array();

    
    /**
     * Storage for sql dump
     * @var string
     */
    protected $sql;


    /**
     * Adds sql to buffer
     * @param string $sql
     */
    public function addToBuffer($sql)
    {
	$this->sql .= $sql ."\n";
    }


    /**
     * @param Field $field
     * @param PerfORM $model
     */
    public function addField($field)
    {
	$template= $this->getTemplate('field-add');
	$template->field= $this->getField($field);
	$this->renderToBuffer($template);
    }

    /**
     * @param Field $field
     * @param PerfORM $model
     */
    public function renameField($field, $from)
    {
	$template= $this->getTemplate('field-rename');
	$template->field= $this->getField($field, $from);
	$this->renderToBuffer($template);
    }


    /**
     * @param Field $field
     * @param PerfORM $model
     */
    public function renameIndex($index, $from)
    {
	$template= $this->getTemplate('index-rename');
	$template->index= $this->getIndex($index, $from);
	$this->renderToBuffer($template);
    }


    /**
     * @param PerfORM $model
     * @param string $from
     */
    public function renameTable($model, $from)
    {
	$template= $this->getTemplate('table-rename');
	$template->table= $this->getRenameTable($model, $from);
	$this->renderToBuffer($template);
    }


    /**
     * @param Field $field
     * @param PerfORM $model
     */
    public function dropField($fieldName, $model)
    {
	$template= $this->getTemplate('field-drop');
	$template->field= $this->getDropField($fieldName, $model);
	$this->renderToBuffer($template);
    }


    /**
     * @param Field $field
     * @param PerfORM $model
     */
    public function dropIndex($indexName, $model)
    {
	$template= $this->getTemplate('index-drop');
	$template->index= $this->getDropIndex($indexName, $model);
	$this->renderToBuffer($template);
    }


    /**
     * @param PerfORM $model
     */
    public function createTable($model)
    {
	$this->createTables[]= $model;
    }


    /**
     * @param Index $index
     */
    public function createIndex($index)
    {
	$template= $this->getTemplate('index-create');
	$template->index= $this->getIndex($index);
	$this->renderToBuffer($template);
    }


    /**
     * @param PerfORM|string $model
     */
    public function dropTable($model)
    {
	if ( is_object($model))
	{
	    $this->dropModelTables[]= $model;
	}
	else {
	    $this->dropTables[]= $model;
	}
    }


    public function changeFieldsNullable($field)
    {
	$template= $this->getTemplate('field-change-nullable');
	$template->field= $this->getField($field);
	$this->renderToBuffer($template);
    }
    

    public function changeFieldsDefault($field)
    {
	$template= $this->getTemplate('field-change-default');
	$template->field= $this->getField($field);
	$this->renderToBuffer($template);
    }

    abstract protected function getDriver();

    abstract protected function getField($field, $renameFrom = null);

    abstract public function translateDefault($field);

    public function getTemplate($templateFile) {
	$template= new Template();
	$template->registerFilter(new LatteFilter);
	$template->setFile( dirname(__FILE__).'/'. $this->getDriver() . '/templates/'. $templateFile .'.psql');
	return $template;
    }


    /**
     * Renders sql template and adds it to dump buffer
     * @param Template $template
     * @return string
     */
    public function renderToBuffer($template, $atBeginning= false) {
	if ( $atBeginning)
	{
	    $this->sql = $this->renderTemplate($template). "\n" . $this->sql;
	}
	else {
	    $this->sql .= $this->renderTemplate($template). "\n";
	}
    }


    /**
     * Renders sql template and removes unnecessary empty lines
     * @param Template $template
     * @return string
     */
    public function renderTemplate($template) {
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
	for($i=0; $i<2; $i++)
	{
	    $_list= $list;
	    foreach($_list as $item)
	    {
		$min= false;
		foreach($item->getDependents() as $dependent)
		{
		    $min= ( $min === false ) ?  $this->model_array_search($dependent, $list) : min($min, $this->model_array_search($dependent, $list)) ;
		}
		if ( is_integer($min) && $this->model_array_search($item, $list) > $min)
		{
		    unset($list[$this->model_array_search($item,$list)]);
		    $list= $this->insertArrayIndex($list, $item, $min);
		}
	    }
	}
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
    

    public function getSql()
    {
	# dependancy solving for create tables
	if ( count($this->createTables)>0)
	{
	    $template= $this->getTemplate('tables-create');
	    $template->tables= array();
	    self::dependancySortReverse($this->createTables);
	    foreach($this->createTables as $model)
	    {
		$template->tables[]= $this->getCreateTable($model);
	    }
	    $this->renderToBuffer($template, true);
	}

	# dependancy solving for drop tables
	if ( count($this->dropModelTables)>0 or count($this->dropTables)>0)
	{
	    $template= $this->getTemplate('tables-drop');
	    $template->tables= array();
	    self::dependancySort($this->dropModelTables);
	    foreach($this->dropModelTables as $model)
	    {
		$template->tables[]= $model->getTableName();
	    }
	    foreach($this->dropTables as $table)
	    {
		$template->tables[]= $table;
	    }
	    $this->renderToBuffer($template, true );
	}
	
	return $this->sql;
    }
}

