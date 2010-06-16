<?php


/**
 * PerfORMTyreTest
 *
 * @author kraken
 * @abstract
 *
 */
abstract class PerfORMTyreTest extends PerfORMDocument
{

    protected function setup()
    {
	$this->addSmallIntegerField('rating')->setNullable();
	$this->addCharField('title', 200)->setNullable();
    }
}
