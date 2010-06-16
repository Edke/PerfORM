<?php

/**
 * PerfORMVyrobca
 *
 * @author kraken
 * @abstract
 */
abstract class PerfORMVyrobca extends PerfORM {

    protected function setup()
    {
	$this->addCharField('nazov', 35)->setNotNullable();
	$this->addCharField('kod', 15)->setNotNullable()->setNullCallback('nullKod');
	$this->addCharField('typ', 6)->setNotNullable(); // ChoicesField
	$this->addSmallIntegerField('poradie')->setNotNullable();
	$this->addBooleanField('aktivne')->setNotNullable();
	$this->addCharField('farba', 6)->setNullable()->setDefault('DDDDDD');
	$this->addCharField('skratka', 6)->setNotNullable()->addUnique();
    }


    public function __toString()
    {
	return $this->nazov;
    }



    public function nullKod()
    {
	return String::webalize($this->nazov);
    }

}