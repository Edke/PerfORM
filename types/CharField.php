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
	    if ( preg_match('#^max_length=([0-9]+)$#i', $option, $matches) ) {
		$this->setSize( $matches[1]);
		$options->remove($option);
	    }
	    else{
		$this->addError("unknown option '$option'");
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


    public function getSize() {
	return $this->size;
    }


    public function getHash()
    {
	if ( !$this->hash )
	{
	    $this->hash= md5($this->getSize().'|'.parent::getHash());
	}
	return $this->hash;
    }


    final public function retyped($value) {
	return (string) $value;
    }

    public function validate() {
	if ( is_null($this->size)) {
	    $this->addError("required option max_length was not set");
	}
	return parent::validate();
    }


    static public function getPhpDocProperty()
    {
	return 'string';
    }
}

