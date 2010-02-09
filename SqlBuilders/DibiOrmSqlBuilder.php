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

    
    /**
     * Storage for sql dump
     * @var string
     */
    protected $sql;

    
    /**
     * @param Field $field
     * @param DibiOrm $model
     */
    public function addField($field)
    {
	$template= $this->getTemplate('field-add');
	$template->field= $this->getField($field);
	$this->renderToBuffer($template);
    }

    /**
     * @param Field $field
     * @param DibiOrm $model
     */
    public function renameField($field, $from)
    {
	$template= $this->getTemplate('field-rename');
	$template->field= $this->getField($field, $from);
	$this->renderToBuffer($template);
    }

    
    /**
     * @param DibiOrm $model
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
     * @param DibiOrm $model
     */
    public function dropField($fieldName, $model)
    {
	$template= $this->getTemplate('field-drop');
	$template->field= $this->getDropField($fieldName, $model);
	$this->renderToBuffer($template);
    }


    /**
     * @param DibiOrm $model
     */
    public function createTable($model)
    {
	$this->createTables[]= $model;
    }


    /**
     * @param DibiOrm|string $model
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
    public function renderToBuffer($template) {
	$this->sql .= $this->renderTemplate($template). "\n";
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
    

    public function getSql()
    {
	# dependancy solving for create tables
	if ( count($this->createTables)>0)
	{
	    $template= $this->getTemplate('tables-create');
	    self::dependancySortReverse($this->createTables);
	    foreach($this->createTables as $model)
	    {
		$template->tables[]= $this->getCreateTable($model);
	    }
	    $this->renderToBuffer($template);
	}

	# dependancy solving for drop tables
	if ( count($this->dropModelTables)>0 or count($this->dropTables)>0)
	{
	    $template= $this->getTemplate('tables-drop');
	    self::dependancySort($this->dropModelTables);
	    foreach($this->dropModelTables as $model)
	    {
		$template->tables[]= $model->getTableName();
	    }
	    foreach($this->dropTables as $table)
	    {
		$template->tables[]= $table;
	    }
	    $this->renderToBuffer($template);
	}
	
	return $this->sql;
    }
}

