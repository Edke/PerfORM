<?php
/**
 * Description of StringField
 *
 * @author kraken
 */
final class ForeignKeyField extends Field {

    protected $type = Dibi::FIELD_INTEGER;

    /**
     * @var DibiOrm
     */
    protected $reference;

    protected $referenceKey;

    protected $nameMask= '%foreignKeyName_%ownName';

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
	return $value;
    }

    public function getReference() {
	return $this->reference;
    }

    public function setName($name) {
	parent::setName($name);

	$this->referenceKey= $this->reference->getPrimaryKey();
	$dbName=str_replace('%ownName', $name, $this->nameMask);
	$dbName=str_replace('%foreignKeyName', $this->referenceKey, $dbName);
	parent::setDbName($dbName);
    }

    protected function setDbName($dbName) {
	$this->addError("forbidden to set db_column for foreign key, change mask instead");
	return false;
    }

    public function getReferenceTableName(){
	return $this->reference->getTableName();
    }

    public function getReferenceTableKey() {
	return $this->referenceKey;
    }

    public function isForeignKey() {
	return true;
    }

    public function getValue()
    {
	$value= $this->value->getField($this->referenceKey)->getValue();
	if ( !is_integer($value)) {
	    $value= $this->value->save();
	}
	return $value;
    }

}
