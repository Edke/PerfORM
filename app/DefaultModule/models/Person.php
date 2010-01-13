<?php
/**
 * Person
 *
 * @author kraken
 *
 * @property-write string $name
 *
 */
class Person extends Orm {

    protected function setup()
    {
	$this->name = new CharField('max_length=200', 'notnull'/*, 'db_column=meno'*/ );
	$this->gender = new CharField('null', 'default=m');
	$this->age= new IntegerField('notnull');
	//$this->test_case = new IntegerField('null', 'default=b');
    }

}





