<?php
/**
 * Description of StringField
 *
 * @author kraken
 */
final class ForeignKeyField extends Field {

    protected $type = Dibi::FIELD_INTEGER;
    
    protected $reference;

    public function  __construct()
    {
	$options= parent::__construct(func_get_args());

	foreach ( $options as $option){
	    if ( is_object($option) && is_subclass_of($option, 'DibiOrm')) {
		$this->reference= $option;
		$options->remove($option);
	    }
	    else{
		$this->addError("unknown option '$option'");
	    }
	}
    }

    final public function retyped($value) {
	return (int) $this->reference;
    }

    public function getReference() {
	return $this->reference;
    }
    
}



