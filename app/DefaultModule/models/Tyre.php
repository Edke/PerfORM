<?php
/**
 * Tyre
 *
 * @author kraken
 */
class Tyre extends Orm {

    protected function setup()
    {
	$this->sirka= new StringField('max_length=200', 'notnull' );
	$this->vyska = new IntegerField('null');
    }
    

}





