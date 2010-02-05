<?php
/**
 * Description of StringField
 *
 * @author kraken
 */
class IntegerField extends Field {
    
    protected $type = Dibi::FIELD_INTEGER;

    public function __construct()
    {
	$options= parent::__construct(func_get_args());

	foreach ( $options as $option) {
	    $this->addError("unknown option '$option'");
	}
    }

    final public function retyped($value) {
	return (int) $value;
    }

    
    static public function getPhpDocProperty()
    {
	return 'integer';
    }
}



