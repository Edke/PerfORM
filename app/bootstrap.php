<?php

// Step 1: Load Nette Framework
//require_once LIBS_DIR .'/Nette/loader.compact.php';
require_once LIBS_DIR .'/Nette/loader.php';


// Step 2: Configure and setup application environment
// 2a) RobotLoader
$robot = new RobotLoader();
$robot->addDirectory(APP_DIR);
$robot->addDirectory(LIBS_DIR);
$robot->autoRebuild = true;
$robot->ignoreDirs= '.*, *.old, *.bak, *.tmp, temp, fshl_cache';
$robot->register(); 

// 2b) load configuration from config.ini file
//$old_include_path= ini_get('include_path');
Environment::loadConfig();

// 2c) merge server directive include_path with nette configuration
//ini_set( 'include_path', $old_include_path .':'.ini_get('include_path') );

// 2d) various application configurations
$config= Environment::getConfig('application');

// 2e) enable Nette::Debug for better exception and error visualisation
$emailHeaders = array(
	'From' => $config->maildaemon,
	'To'   => $config->webmaster,
	'Subject' => 'Nette::Bug - %host% - %date%',
	'Body' => "host: %host%\ndate: %date%",
);
Debug::enable(E_ALL, NULL, $emailHeaders);

// 2f) profiler in development mode
if ( $config->profiler ) {
	Debug::enableProfiler();
	Debug::$counters["Nette revision"] = Framework::REVISION;
}

// 2g) set locales 
setlocale(LC_CTYPE, $config->locale );
setlocale(LC_TIME, $config->locale );

// 2h) check if directory /app/temp is writable
if (!is_writable(Environment::getVariable('tempDir'))) {
	throw new Exception("Make directory '" . Environment::getVariable('tempDir') . "' writable!");
}

// 2i) establish database connection
require_once LIBS_DIR . '/dibi/dibi.php';
//require_once LIBS_DIR . '/dibi/dibi.compact.php';

dibi::connect(Environment::getConfig('database'));

if ($config->routingDebugger){
	RoutingDebugger::enable();
}

# services 
//Environment::getServiceLocator()->addService( new AclFrontend, 'Nette\Security\IAuthorizator' );
//Environment::getServiceLocator()->addService( new App_Uzivatel, 'Nette\Security\IAuthenticator' );


$application = Environment::getApplication();
// Step 3: Get the front controller
if ( Environment::isProduction() ) {
	$application->errorPresenter = 'Error';
	$application->catchExceptions = true;
}

// Step 4: Setup application routes
$router = $application->getRouter();

$router[] = new Route('<presenter>/<action>/<id>', array(
	'module' => 'Default',
	'presenter' => 'Default',
	'action' => 'default',
	'id' => NULL,
));

// Step 5: Run the application!
$application->run();
