<?php

/**
 * Description of Field
 *
 * @author kraken
 */
abstract class Field {

    protected $column;

    /**
     * @var boolean
     */
    protected $primaryKey= false;

    /**
     * @var boolean
     */
    protected $isNull = null;

    /**
     * @var mixed
     */
    protected $default = null;


    /**
     * @var string
     */
    protected $dbColumn= null;

    protected $type;

    protected $value= null;

    public function __construct($_options)
    {
	$options= new Set();
	$options->import($_options);

	foreach ( $options as $option){
	    if ( strtolower($option) == 'null' ) {
		$this->setNull();
		$options->remove($option);
	    }
	    elseif ( strtolower($option) == 'notnull' ) {
		$this->setNotNull();
		$options->remove($option);
	    }
	    elseif ( preg_match('#default=(.+)#i', $option, $matches) ) {
		$this->setDefault( $matches[1]);
		$options->remove($option);
	    }
	    
	    elseif ( preg_match('#db_column=(.+)#i', $option, $matches) ) {
		$this->setDbColumn( $matches[1]);
		$options->remove($option);
	    }
	    elseif ( strtolower($option) == 'primary_key' ) {
		$this->setPrimaryKey();
		$options->remove($option);
	    }
	}

	return $options;
    }

    protected function setNotNull()
    {
	if( !is_null($this->isNull) ) {
	    throw new Exception('field already has null/notnull');
	}
	$this->isNull= false;
    }

    protected function setNull()
    {
	if( !is_null($this->isNull) ) {
	    throw new Exception('field already has null/notnull');
	}
	$this->isNull= true;
    }

    final public function setDefault($default) {
	if( !is_null($this->default) ) {
	    throw new Exception("field already has default value '$this->default'");
	}

	$retypedDefault= $this->retyped($default);
	if ( (string) $default != (string) $retypedDefault ) {
	    throw new Exception("invalid datatype of default value '$default'");
	}

	$this->default= $retypedDefault;
    }

    protected function setDbColumn($dbColumn) {
	if( !is_null($this->dbColumn) ) {
	    throw new Exception("field already has db_column '$this->dbColumn'");
	}
	$this->dbColumn= $dbColumn;
    }

    public function setName($column) {
	$this->column= $column;
    }

    public function setValue($value) {
	$this->value= $this->retyped($value);
    }

    abstract function retyped($value);

    public function setPrimaryKey()
    {
	$this->primaryKey= true;
    }

    public function getValue()
    {
	return $this->value;
    }

    public function getName()
    {
	return $this->column;
    }

    public function getRealName()
    {
	if ( $this->dbColumn ) {
	    return $this->dbColumn;
	}
	return $this->column;
    }

    public  function getDefaultValue() {
	return $this->default;
    }

    public function isPrimaryKey() {
	return $this->primaryKey;
    }

    public function isNotNull() {
	return $this->isNull && false;
    }

    public function getType() {
	return $this->type;
    }

}

