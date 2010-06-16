<?php

/**
 * PerfORMDezen
 *
 * @author kraken
 * @abstract
 */
abstract class PerfORMDezen extends PerfORM {

    protected $prefix= 'pneumatika_';

    protected function setup()
    {
	$this->addForeignKeyField('vyrobca', 'Vyrobca')->setNotNullable()->addIndex();
	$this->addCharField('nazov', 40)->setNotNullable();
	$this->addCharField('unikatny_nazov', 40)->setNotNullable()->setNullCallback('nullUnikatnySubor');
	$this->addCharField('sezona', 1)->setChoices('getSeasons');

	$this->addIndex(array('vyrobca','unikatny_nazov'), 'vyrobca_nazov', true);
    }


    public function __toString()
    {
	return $this->nazov;
    }


    public function nullUnikatnySubor()
    {
	return String::webalize($this->nazov);
    }

    public function getSeasons()
    {
	return array(
	'L' => 'letná',
	'Z' => 'zimná',
	'C' => 'celoročná'
	 );
    }
}