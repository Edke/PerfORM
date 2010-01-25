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
 * DibiOrmModelLoader
 *
 * Loader heavilly extended from great David Grudl's RobotLoadet of Nette
 *
 * Loader searches for classes of app/ directory, it creates instance and
 * finds if subclass of DibiOrm (ugly but working so far)
 *
 * Class is also helping find out mtimes of models class files for caching purposes
 *
 * @copyright Copyright (c) 2004, 2010 David Grudl
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package DibiOrm
 */

class DibiOrmModelLoader
{
    /** @var array */
    public $scanDirs;

    /** @var string  comma separated wildcards */
    public $ignoreDirs = '.*, *.old, *.bak, *.tmp, log*, templates, temp, presenters';

    /** @var string  comma separated wildcards */
    public $acceptFiles = '*.php, *.php5';

    /** @var array */
    private $list = array();

    /** @var array */
    private $timestamps = array();

    /** @var string */
    private $acceptMask;

    /** @var string */
    private $ignoreMask;

    /** @var array all models of application */
    private $models= array();

    /** @var bool */
    private $rebuilded = FALSE;


    /**
     */
    public function __construct()
    {
	if (!extension_loaded('tokenizer'))
	{
	    throw new Exception("PHP extension Tokenizer is not loaded.");
	}
    }


    /**
     * Add class and file name to the list.
     * @param  string
     * @param  string
     * @return void
     */
    public function addClass($class, $file)
    {
	$class = strtolower($class);
	$this->list[$class] = $file;
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
     * Getter for Cache
     * @return Cache
     */
    protected function getCache()
    {
	return DibiOrmController::getCache();
    }


    /**
     * Getter for cache key
     * @return string
     */
    protected function getKey()
    {
	return md5("$this->ignoreDirs|$this->acceptFiles|" . implode('|', $this->scanDirs));
    }


    /**
     * Getter for models found in application
     * @return array
     */
    public function getModels()
    {
	return $this->models;
    }


    public function init()
    {
	$cache= $this->getCache();
	$key= $this->getKey();

	if ( isset($cache[$key]) and isset($cache[$key . 'models']) )
	{
	    $this->list= $cache[$key];
	    #$this->timestamps= $cache[$key . 'ts'];
	    $this->models= $cache[$key . 'models'];
	}
	else
	{
	    $this->rebuild();
	}
    }


    /**
     * Rebuilds class list cache.
     * @param  bool
     * @return void
     */
    public function rebuild($force = TRUE)
    {
	$cache= $this->getCache();
	$key= $this->getKey();

	$this->acceptMask = self::wildcards2re($this->acceptFiles);
	$this->ignoreMask = self::wildcards2re($this->ignoreDirs);

	if ($force || !$this->rebuilded)
	{
	    foreach (array_unique($this->scanDirs) as $dir)
	    {
		$this->scanDirectory($dir);
	    }

	    foreach($this->list as $file => $path)
	    {
		$class= new $file;
		if ( is_subclass_of($class, 'DibiOrm') )
		{
		    $this->models[]= get_class($class);
		}
	    }
	}

	$cache[$key] = $this->list;
	#$cache[$key . 'ts'] = $this->timestamps;
	$cache[$key . 'models'] = $this->models;
	$this->rebuilded = TRUE;
    }


    /**
     * Scan a directory for PHP files, subdirectories and 'netterobots.txt' file.
     * @param  string
     * @return void
     */
    private function scanDirectory($dir)
    {
	$iterator = dir($dir);
	if (!$iterator) return;

	$disallow = array();
	if (is_file($dir . '/netterobots.txt'))
	{
	    foreach (file($dir . '/netterobots.txt') as $s)
	    {
		if (preg_match('#^disallow\\s*:\\s*(\\S+)#i', $s, $m))
		{
		    $disallow[trim($m[1], '/')] = TRUE;
		}
	    }
	    if (isset($disallow[''])) return;
	}

	while (FALSE !== ($entry = $iterator->read()))
	{
	    if ($entry == '.' || $entry == '..' || isset($disallow[$entry])) continue;

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
		$time = filemtime($path);
		if (!isset($this->timestamps[$path]) || $this->timestamps[$path] !== $time)
		{
		    $this->timestamps[$path] = $time;
		    $this->scanScript($path);
		}
	    }
	}

	$iterator->close();
    }


    /**
     * Analyse PHP file.
     * @param  string
     * @return void
     */
    private function scanScript($file)
    {
	if (!defined('T_NAMESPACE'))
	{
	    define('T_NAMESPACE', -1);
	    define('T_NS_SEPARATOR', -1);
	}

	$expected = FALSE;
	$namespace = '';
	$level = 0;
	$s = file_get_contents($file);

	if (preg_match('#//nette'.'loader=(\S*)#', $s, $matches))
	{
	    foreach (explode(',', $matches[1]) as $name)
	    {
		$this->addClass($name, $file);
	    }
	    return;
	}

	foreach (token_get_all($s) as $token)
	{
	    if (is_array($token))
	    {
		switch ($token[0])
		{
		    case T_COMMENT:
		    case T_DOC_COMMENT:
		    case T_WHITESPACE:
			continue 2;

		    case T_NS_SEPARATOR:
		    case T_STRING:
			if ($expected)
			{
			    $name .= $token[1];
			}
			continue 2;

		    case T_NAMESPACE:
		    case T_CLASS:
		    case T_INTERFACE:
			$expected = $token[0];
			$name = '';
			continue 2;
		    case T_CURLY_OPEN:
		    case T_DOLLAR_OPEN_CURLY_BRACES:
			$level++;
		}
	    }

	    if ($expected)
	    {
		switch ($expected)
		{
		    case T_CLASS:
		    case T_INTERFACE:
			if ($level === 0)
			{
			    $this->addClass($namespace . $name, $file);
			}
			break;

		    case T_NAMESPACE:
			$namespace = $name . '\\';
		}

		$expected = NULL;
	    }

	    if ($token === '{')
	    {
		$level++;
	    } elseif ($token === '}')
	    {
		$level--;
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
