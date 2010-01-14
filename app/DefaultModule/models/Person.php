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
	$this->name = new CharField('max_length=200', 'notnull'/*, 'db_column=meno'*/);
	$this->gender = new CharField('max_length=1', 'null', 'default=m');
	$this->age= new IntegerField('notnull', 'default=3');
	$this->test_case = new IntegerField('null', 'default=5');
/*	$this->test2 = new IntegerField('notnull');*/
    }

}





