<?php


/**
 * PerfORMDocument
 *
 * @author kraken
 * @abstract
 *
 */
abstract class PerfORMDocument extends PerfORM
{

    protected function setup()
    {
	$this->addCharField('title', 200)->setNotNullable();
	$this->addSlugField('slug', 200, 'title');
	$this->addDateTimeField('modified')->enableAutoNow()->setNotNullable();
	$this->addDateTimeField('created')->enableAutoNowAdd()->setNotNullable();
	$this->addTextField('content')->setNotNullable();
    }

    public function __toString()
    {
	return $this->title;
    }


    public function nullSlug()
    {
	return $this->title;
    }
}
