<?php
/**
 * Description of dibiOrmPostgre
 *
 * @author kraken
 */
class DibiOrmPostgreDriver {

    CONST DRIVER = 'postgre';

    /**
     * @var boolean
     */
    protected $addDropTable= false;


    protected function getTemplate($templateFile) {
	$template= new Template();
	$template->registerFilter('LatteFilter::invoke');
	$template->setFile($templateFile);

	return $template;
    }


    protected function translateType($field){
	$fieldClass= get_class($field);

	switch ($fieldClass)
	{
	    case 'AutoField':
		return 'serial';

	    case 'IntegerField':
		return 'integer';


	    case 'CharField':
		return sprintf('character varying(%d)', $field->getSize());

	    default:
		throw new Exception("datatype for class '$fieldClass' not recognized by translator");
		
	}
    }

    /**
     *
     * @param Orm $orm
     * @return string
     */
    public function createTable($orm) {
	
	$template= $this->getTemplate( dirname(__FILE__). '/'. self::DRIVER . '-create-table-dll.psql' );

	


	$fields= array();
	foreach($orm->getFields() as $field) {

	    $fields[]= (object) array(
		'name' => $field->getRealName(),
		'type' => $this->translateType($field),

	    );


	    
	}
	Debug::consoleDump($orm->getFields());
	Debug::consoleDump($fields);


	$template->fields= $fields;
	$template->keys= array();
	$template->indexes= array();
	$template->table= $orm;

	ob_start();
	$template->render();
	return ob_get_clean();
    }


    protected function createFromMigrator() {

	$dll= ( $this->addDropTable ) ? "\nDROP TABLE [$table];\n" : "\n";


		
		$dll .= "CREATE TABLE [$table] (\n";
		$rows= array();
		$keys= array();


		// columns
		$result= dibi::query("show full columns from $table");
		foreach( $result as $row) {

			// primary key -> serial
			if ( $row->Key == 'PRI' and $row->Extra == 'auto_increment' and $this->types[$row->Type]['create'] == 'INTEGER'  ) {
				$rows[]= sprintf("\t[%s] SERIAL", $row->Field);
				$keys[]= sprintf("\tCONSTRAINT [%s_pkey] PRIMARY KEY([%s])", $table, $row->Field);
			}
			// other rows
			else {
				// default values
				$default= null;
				if ( $row->Default ) {
					$default= " DEFAULT \"". $row->Default ."\"";
				}

				//default values override
				if ( ($this->types[$row->Type]['create'] == 'TIMESTAMP'  and is_string($default)) or
						($this->types[$row->Type]['create'] == 'DATE' and is_string($default)) ) {
					$default= null;
				}

				//default values override for enum->boolean
				if ( $this->types[$row->Type]['create'] == 'BOOLEAN'  and is_string($default)) {

					if ( !preg_match('#[ya1n0]{1}#', $default )) throw new Exception("invalid default value for boolean: '$default'");
					$default= ( preg_match('#[ya1]{1}#', $default ) )? ' DEFAULT true' : ' DEFAULT false';
				}

				$rows[]= sprintf("\t[%s] %s%s%s",
					$row->Field,
					$this->types[$row->Type]['create'],
					( $row->Null == 'YES' )? ' NULL': ' NOT NULL',
					$default
					);
			}
		}

		$_indexes= array();

		// indexes
		$result= dibi::query("show index from $table");
		foreach( $result->fetchAssoc('Key_name,Seq_in_index,@') as $key => $row ) {

			// skip primary
			if ( $key != 'PRIMARY'  ) {
				foreach( $row as $index ){

					//var_dump( $key );
					//var_dump( $index );

					// skip FULLTEXT
					if ( $index->Index_type == 'BTREE' ) {
						$_indexes[$table.'_'. $key .'_idx']['fields'][]= $index->Column_name;
						$_indexes[$table.'_'. $key .'_idx']['unique']= ($index->Non_unique)? false : true;
					}


				}
			}

		}
		$indexes= array();
		foreach( $_indexes as $indexName =>  $index ){

			// unique constraint
			if ( $index['unique'] and sizeof( $index['fields']) == 1 ) {
				$keys[]= sprintf("\tCONSTRAINT [%s] UNIQUE([%s])", $indexName, implode( "],[", $index['fields'] ));
			}

			// index
			else {
				$indexes[]= sprintf("CREATE %sINDEX [%s] ON [%s]\n\tUSING btree ([%s]);",
					( $index['unique'] ) ? 'UNIQUE ': '',
					$indexName,
					$table,
					implode( "],[", $index['fields'] ));

			}
		}

		$dll .= implode( ",\n",  array_merge($rows, $keys) ) . "\n";
		$dll .= ") WITH OIDS;\n\n";

		if ( sizeof($indexes)>0) {
			$dll .= "\n\n" . implode( "\n\n", $indexes );

		}

		// handle actions
		$formated_sql= dibi::getConnection('postgre')->sql($dll);
		if ( $this->isAction('print') or $this->isVerbose() ) {
			echo $formated_sql;
		}
		elseif ( $this->isAction('dump')) {
			$this->write($formated_sql);
		}
		elseif ( $this->isAction('insert')) {
			dibi::getConnection('postgre')->query($dll);
		}
	}





}

