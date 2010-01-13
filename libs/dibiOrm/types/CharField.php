<?php
/**
 * Description of StringField
 *
 * @author kraken
 */
final class CharField extends Field {

    protected $size;

    protected $type = Dibi::FIELD_TEXT;

    public function  __construct()
    {
	$options= parent::__construct(func_get_args());

	foreach ( $options as $option){
	    if ( preg_match('#max_length=([0-9]+)#i', $option, $matches) ) {
		$this->setSize( $matches[1]);
		$options->remove($option);
	    }
	    else{
		throw new Exception ("invalid option '$option'");
	    }
	}
    }

    protected function  setSize($size){
	$size= (int) $size;
	if ( !$size>0) {
	    throw new Exception("invalid size '$size'");
	}
	$this->size= $size;
    }


    final public function retyped($value) {
	return (string) $value;
    }


}



