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
 * Freeze exception
 *
 * @copyright Copyright (c) 2010 Eduard 'edke' Kracmar
 * @package PerfORM
 */
class FreezeException extends Exception
{

    public function __construct($message = '', $code = 0, Exception $previous = NULL)
    {
	parent::__construct('Definition was frozen for further modification', $code, $previous);
    }
}