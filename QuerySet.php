<?php
/**
 * queryset
 *
 * @author kraken
 */
class QuerySet
{
    /**
     * @var DibiOrm
     */
    protected $orm;

    /**
     *
     * @var DibiDataSource
     */
    protected $dataSource;

    protected $fields= array();

    protected $joins= array();

    public function  __construct(DibiOrm $orm)
    {
	$this->orm= $orm;

	$query= array();

	$query[]= 'SELECT';
	$this->addFields($orm);
	$query[]= implode(",\n",$this->fields);
	$query[]= sprintf("FROM %s", $orm->getTableName() );
	$this->addJoins($orm);
	$query[]= implode(",\n",$this->joins);

	$sql= implode("\n", $query);

	$this->dataSource= new DibiDataSource($sql, DibiOrmController::getConnection());

	$this->dataSource->fetch();
	//Debug::consoleDump(dibi::$sql, 'sql');
	//Debug::consoleDump(count($this->dataSource));
	//Debug::consoleDump($query, 'query');
	
	return $this;
    }

    protected function addFields($orm)
    {
	foreach( $orm->getFields() as $field )
	{
	    $this->fields[]= sprintf("\t%s.%s as %s__%s", $orm->getTableName(), $field->getRealName(), $orm->getTableName(), $field->getRealName() );
	    if ( get_class($field) == 'ForeignKeyField') {
		$this->addFields($field->getReference());
	    }
	}
    }

    /**
     * @param DibiOrm $orm
     */
    protected function addJoins($orm)
    {
	foreach( $orm->getFields() as $field )
	{
	    if ( get_class($field) == 'ForeignKeyField') {
		$this->joins[]= sprintf("\tINNER JOIN %s ON %s.%s = %s.%s",
		    $field->getReference()->getTableName(),
		    $field->getReference()->getTableName(),
		    $field->getReferenceTableKey(),
		    $orm->getTableName(),
		    $field->getRealName()
		);
		$this->addJoins($field->getReference());
	    }
	}

    }



    public function get()
    {
	$options= new Set();
	$options->import(func_get_args());

	foreach ( $options as $option){
	    if ( preg_match('#^(pk|id)=([0-9]+)$#i', $option, $matches) ) {
		$primaryKeyValue = $matches[2];
	    }
	    else{
		throw new Exception("unknown option '$option'");
	    }
	}
	$primaryField= $this->orm->getField($this->orm->getPrimaryKey());

	$this->dataSource->where(
	    '%n = %'.$primaryField->getType(),
	    $this->orm->getTableName().'__'.$primaryField->getRealName(),
	    $primaryKeyValue
	);

	//Debug::consoleDump($this->dataSource->fetch(), 'fetch');
	
    }



    public function all()
    {


//	foreach()
	
	

    }

}
