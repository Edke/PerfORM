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
     * Add class and file name to the list.
     * @param  string
     * @param  string
     * @return void
     */
    public function addModel($modelInfo)
    {
	$this->models[]= $modelInfo;
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
	$this->acceptMask = self::wildcards2re($this->acceptFiles);
	$this->ignoreMask = self::wildcards2re($this->ignoreDirs);

	$modelCacheDir= Environment::getConfig('dibiorm')->modelCache;

	if ( !file_exists($modelCacheDir))
	{
	    throw new Exception("Model cache directory '$modelCacheDir' does not exists");
	}

	if ( !is_writable($modelCacheDir) )
	{
	    throw new Exception("Model cache directory '$modelCacheDir' is not writable.");
	}


	foreach (array_unique($this->scanDirs) as $dir)
	{
	    $this->scanDirectory($dir);
	}

	#clean model cache
	
	$iterator = dir($modelCacheDir);
//	if (!$iterator) return;
	while (FALSE !== ($entry = $iterator->read()))
	{
	    if ($entry == '.' || $entry == '..') continue;

	    $path = $modelCacheDir . DIRECTORY_SEPARATOR . $entry;

	    if (is_file($path) && preg_match($this->acceptMask, $entry))
	    {
		unlink($path);
	    }
	}
	$iterator->close();

	$_template= file_get_contents(dirname(__FILE__).'/cacheModelTemplates/cache-model.phptemplate');
	foreach($this->models as $modelInfo)
	{
	    $template= $_template;
	    $template= str_replace('%lastModification%', time(), $template);
	    $template= str_replace('%modelName%', $modelInfo->model, $template);
	    $template= str_replace('%modelBase%', $modelInfo->extends, $template);
	    $template= str_replace('%setup%', $modelInfo->setup, $template);
	    $template= str_replace('%source%', $modelInfo->path, $template);


//	    Debug::consoleDump($modelInfo);

	    $properties= null;
	    foreach( $modelInfo->fields as $field)
	    {
		$fieldType= new $field->type;
		$properties .= sprintf(" * @property-write %s \$%s\n", $fieldType->getPhpDocProperty(), $field->name);
	    }
	    $template= str_replace('%properties%', $properties, $template);
	    file_put_contents($modelCacheDir . DIRECTORY_SEPARATOR . $modelInfo->model.'.php', $template);
	}



	
	global $robot;
	//Debug::consoleDump($robot);
	//$robot->rebuild(true);
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
		$_fields_hashes= array();
		if ( preg_match_all('#\$this\-\>([^= ]+)\s*=\s*new\s*([a-z]+)\s*\((.+)\)#i', $class[3][$key], $field))
		{
		    foreach($field[0] as $field_key => $field_value)
		    {
			$options= preg_split('#,\s*#', $field[3][$field_key]);
			//Debug::consoleDump($options);
			array_walk($options, array($this, 'removeSpaces'));
			sort($options);
			$hash= md5(strtolower(trim($field[2][$field_key]) .'|'. implode(',', $options)));
			$_fields_hashes[]= md5(strtolower($field[1][$field_key]).'|'.$hash);

			$_fields[]= (object) array(
			    'name' => $field[1][$field_key],
			    'type' => $field[2][$field_key],
			    'options' => $options,
			    'hash' => $hash
			);
		    }
		    //Debug::consoleDump($field);
		}
		sort($_fields_hashes);
		$this->addModel( (object) array(
		    'path' => $file,
		    'mtime' => filemtime($file),
		    'extends' => $class[1][$key],
		    'model' => $class[2][$key],
		    'table' => strtolower($class[2][$key]),
		    'setup' => $class[3][$key],
		    'fields' => $_fields,
		    'hash' => md5(implode('|',$_fields_hashes)),
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
