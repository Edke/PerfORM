<?php

/**
 * Nette Framework
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Application
 */


/**
 * Modified CliRouter, uses module, presenter and action from default values
 */
class DibiOrmCliRouter extends CliRouter
{
	const PRESENTER_KEY = 'presenter';

	/**
	 * Maps command line arguments to a PresenterRequest object.
	 * @param  IHttpRequest
	 * @return PresenterRequest|NULL
	 */
	public function match(IHttpRequest $httpRequest)
	{
		$presenterRequest= parent::match($httpRequest);

		$presenterName= $presenterRequest->getPresenterName();
		$params= $presenterRequest->getParams();
		if ( isset($params['module']))
		{
		    $presenterName= $params['module'].':'.ucfirst($presenterName);
		}
		$params[self::PRESENTER_KEY]= $presenterName;
		$defaults= $this->getDefaults();
		if ( isset($defaults['action']) )
		{
		    $params['action']= $defaults['action'];
		}
		return new PresenterRequest(
		    $presenterName,
		    'CLI',
		    $params);
	}
}