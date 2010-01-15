<?php
/**
 * my dibi orm attempt
 *
 * @author kraken
 */
class DibiOrmBase {

    /**
     * @var DibiConnection
     */
    protected $connection;

    /**
     * @var DibiOrmPostgreDriver
     */
    protected $driver;


    /**
     * @return DibiOrmPostgreDriver
     */
    protected function getDriver() {
	if ( !$this->driver) {
	    $driverName= $this->getConnection()->getConfig('driver');
	    $driverClassName= 'DibiOrm'.ucwords($driverName).'Driver';
	    if ( !class_exists($driverClassName)) {
		throw new Exception("driver for '$driverName' not found");
	    }
	    $this->driver= new $driverClassName;
	}
	return $this->driver;
    }

    /**
     * @return DibiConnection
     */
    public function getConnection() {
	if ( !$this->connection) {
	    $this->connection= dibi::getConnection();
	}
	return $this->connection;
    }

}
