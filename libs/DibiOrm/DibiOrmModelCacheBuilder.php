<?php

/**
 * DibiOrm - Object-relational mapping based on David Grudl's dibi
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @copyright  Copyright (c) 2010 Eduard 'edke' Kracmar
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   DibiOrm
 * @package    DibiOrm
 */


/**
 * DibiOrmModelCacheBuilder
 *
 * Builder heavilly extended from great David Grudl's RobotLoadet of Nette
 * Searches for Models in APP directory and builds model's cache
 *
 * @copyright Copyright (c) 2004, 2010 David Grudl
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

class DibiOrmModelCacheBuilder
{
    /** @var array */
    public $scanDirs;

    /** @var string  comma separated wildcards */
    public $ignoreDirs = '.*, *.old, *.bak, *.tmp, log*, templates, temp, presenters';

    /** @var string  comma separated wildcards */
    public $acceptFiles = '*.php, *.php5';

    /** @var string */
    private $acceptMask;

    /** @var string */
    private $ignoreMask;

    /** @var array all models of application */
    private $models= array();


    /**
     * @var array
     */
    private $modelInfo= array();


    /**
     * @var string
     */
    private $modelCacheDir;


    /**
     * Constructor
     */
    public function  __construct()
    {
	/* check if modelCacheDir valid */
	$this->modelCacheDir= realpath(Environment::getConfig('dibiorm')->modelCache);
	if ( !file_exists($this->modelCacheDir))
	{
	    throw new Exception("Model cache directory '$this->modelCacheDir' does not exists");
	}
	if ( !is_writable($this->modelCacheDir) )
	{
	    throw new Exception("Model cache directory '$this->modelCacheDir' is not writable.");
	}
    }


    /**
     * Add class and file name to the list.
     * @param  string
     * @param  string
     * @return void
     */
    public function addModelInfo($modelInfo)
    {
	$this->modelInfo[]= $modelInfo;
    }


    /**
     * Add directory (or directories) to list.
     * @param  string|array
     * @return void
     * @throws DirectoryNotFoundException if path is not found
     */
    public function addDirectory($path)
    {
	foreach ((array) $path as $val)
	{
	    $real = realpath($val);
	    if ($real === FALSE)
	    {
		throw new DirectoryNotFoundException("Directory '$val' not found.");
	    }
	    $this->scanDirs[] = $real;
	}
    }


    /**
     * Getter for models found in application
     * @return array
     */
    public function getModels()
    {
	return $this->models;
    }


    /**
     * Rebuilds class list cache.
     * @param  bool
     * @return void
     */
    public function rebuild()
    {
	/* scan for model bases */
	$this->acceptMask = self::wildcards2re($this->acceptFiles);
	$this->ignoreMask = self::wildcards2re($this->ignoreDirs);
	foreach (array_unique($this->scanDirs) as $dir)
	{
	    $this->scanDirectory($dir);
	}

	/* cleans previous models */
	$iterator = dir($this->modelCacheDir);
	while (FALSE !== ($entry = $iterator->read()))
	{
	    if ($entry == '.' || $entry == '..') continue;

	    $path = $this->modelCacheDir . DIRECTORY_SEPARATOR . $entry;
	    if (is_file($path) && preg_match($this->acceptMask, $entry))
	    {
		unlink($path);
	    }
	}
	$iterator->close();

	/* build new models */
	$_template= file_get_contents(dirname(__FILE__).'/ModelCacheTemplate/ModelCache.phptmpl');
	foreach($this->modelInfo as $modelInfo)
	{
	    $template= $_template;
	    $template= str_replace('%lastModification%', time(), $template);
	    $template= str_replace('%modelName%', $modelInfo->model, $template);
	    $template= str_replace('%modelBase%', $modelInfo->extends, $template);
	    $template= str_replace('%setup%', $modelInfo->setup, $template);
	    $template= str_replace('%source%', $modelInfo->path, $template);

	    $properties= null;
	    foreach( $modelInfo->fields as $field)
	    {
		$properties .= sprintf("\n * @property-write %s \$%s", call_user_func($field->type . '::getPhpDocProperty'), $field->name);
	    }
	    $template= str_replace('%properties%', $properties, $template);
	    file_put_contents($this->modelCacheDir . DIRECTORY_SEPARATOR . $modelInfo->model.'.php', $template);
	}

	/* first run, make models callable */
	foreach($this->modelInfo as $modelInfo)
	{
	    if ( !class_exists($modelInfo->model, false))
	    {
		require_once $this->modelCacheDir . DIRECTORY_SEPARATOR . $modelInfo->model .'.php';
	    }
	}

	/* second run, create new model instances and collection of models */
	foreach($this->modelInfo as $modelInfo)
	{
	    $model= new $modelInfo->model;
	    $this->models[$model->getTableName()]= $model;
	}
    }


    protected function removeSpaces(&$string)
    {
	$string= preg_replace('#\s+#', '', $string);
    }


    /**
     * Scan a directory for PHP files and subdirectories
     * @param  string
     * @return void
     */
    protected function scanDirectory($dir)
    {
	$iterator = dir($dir);
	if (!$iterator) return;

	while (FALSE !== ($entry = $iterator->read()))
	{
	    if ($entry == '.' || $entry == '..') continue;

	    $path = $dir . DIRECTORY_SEPARATOR . $entry;

	    // process subdirectories
	    if (is_dir($path))
	    {
		// check ignore mask
		if (!preg_match($this->ignoreMask, $entry))
		{
		    $this->scanDirectory($path);
		}
		continue;
	    }

	    if (is_file($path) && preg_match($this->acceptMask, $entry))
	    {
		$this->scanScript($path);
	    }
	}

	$iterator->close();
    }


    /**$class[3][$key]
     * Analyse PHP file.
     * @param  string
     * @return void
     */
    protected function scanScript($file)
    {
	//$this->addClass($namespace . $name, $file);
	//$file= '/home/kraken/NetBeansProjects/dibiorm-sandbox/app/models/Author.php';
	$buffer= file_get_contents($file);

	// clean comments
	$buffer= preg_replace('/(\/\/|#).*/', '', $buffer);
	$buffer= preg_replace('#/\*.*\*/#msU', '', $buffer);


	if ( preg_match_all('#abstract\s*class\s*(Base([^ ]+))\s*extends\s*(?:DibiOrm)\s*{.*protected\s*function\s*setup\s*\(\s*\)\s*{(.*)}#imsU', $buffer, $class))
	{
	    foreach($class[0] as $key => $value)
	    {
		//Debug::consoleDump($class);
		//Debug::consoleDump(array($class[1][$key],$class[2][$key],$class[3][$key]));

		$_fields= array();
		$setup= array();
		if ( preg_match_all('#\$this\-\>([^= ]+)\s*=\s*(new\s*([a-z]+)\s*\()(.*)\)#i', $class[3][$key], $field))
		{
		    foreach($field[0] as $field_key => $field_value)
		    {
			$_fields[$field[1][$field_key]]= (object) array(
			    'name' => $field[1][$field_key],
			    'type' => $field[3][$field_key],
			);

			$options= trim($field[4][$field_key]);
			$setup[] = str_replace(
			    $field[2][$field_key],
			    $field[2][$field_key].'$this'. (empty($options) ? '' : ', '),
			    $field[0][$field_key]);
		    }
		}
		$this->addModelInfo( (object) array(
		    'path' => $file,
		    'extends' => $class[1][$key],
		    'model' => $class[2][$key],
		    'table' => strtolower($class[2][$key]),
		    'setup' => "\t". implode(";\n\t", $setup). ";",
		    'fields' => $_fields,
		    ));
	    }
	}
    }


    /**
     * Converts comma separated wildcards to regular expression.
     * @param  string
     * @return string
     */
    private static function wildcards2re($wildcards)
    {
	$mask = array();
	foreach (explode(',', $wildcards) as $wildcard)
	{
	    $wildcard = trim($wildcard);
	    $wildcard = addcslashes($wildcard, '.\\+[^]$(){}=!><|:#');
	    $wildcard = strtr($wildcard, array('*' => '.*', '?' => '.'));
	    $mask[] = $wildcard;
	}
	return '#^(' . implode('|', $mask) . ')$#i';
    }
}
