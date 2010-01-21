<?php
/**
 * Description of dibiOrmPostgre
 *
 * @author kraken
 */
class DibiOrmPostgreDriver extends DibiOrmDriver {

    CONST DRIVER = 'postgre';

    final protected function getDriver() {
	return self::DRIVER;
    }

    protected function translateType($field){
	$fieldClass= get_class($field);

	switch ($fieldClass)
	{
	    case 'AutoField':
		return 'serial';

	    case 'IntegerField':
	    case 'ForeignKeyField':
		return 'integer';

	    case 'CharField':
		return sprintf('character varying(%d)', $field->getSize());

	    default:
		throw new Exception("datatype for class '$fieldClass' not recognized by translator");
		
	}
    }

    protected function translateDefault($field) {

	if ( is_null($field->getDefaultValue())) {
	    return '';
	}

	switch ($field->getType() )
	{
	    case Dibi::FIELD_TEXT:
		return sprintf("DEFAULT '%s'", $field->getDefaultValue());

	    case Dibi::FIELD_INTEGER:
		return sprintf('DEFAULT (%d)', $field->getDefaultValue());

	    case Dibi::FIELD_FLOAT:
		return sprintf('DEFAULT (%d)::double precision', $field->getDefaultValue());

	    default:
		throw new Exception("default for class '$fieldClass' not recognized by translator");
		
	}
    }

    /**
     *
     * @param Orm $orm
     * @return string
     */
    public function createTable($orm) {
	
	$template= $this->getTemplate('create-table.psql' );

	$fields= array();
	foreach($orm->getFields() as $field) {
	    $fields[]= $this->addField($field);
	}

	$keys= array();
	if ( $pk = $orm->getPrimaryKey() ) {
	    $keys[]= $this->addPrimaryKey($orm, $pk);
	}
	foreach( $orm->getForeignKeys() as $foreignKey)
	{
	    $keys[]= $this->addForeignKey($orm, $foreignKey);
	}

	$template->fields= $fields;
	$template->keys= $keys;
	$template->indexes= array();
	$template->table= $orm;

	return $this->renderTemplate($template);
    }

    protected function addField($field) {
	return (object) array(
		'name' => $field->getRealName(),
		'type' => $this->translateType($field),
		'notnull' => ($field->isNotNull()) ? 'NULL' : 'NOT NULL',
		'default' => $this->translateDefault($field),
	    );
    }

    protected function addPrimaryKey($orm, $pk) {
	return (object) array(
		'type' => 'primary',
		'constraint_name' => $orm->getTableName() .'_pkey',
		'field' => $pk
	);
    }

    protected function addForeignKey($orm, $key) {
	return (object) array(
		'type' => 'foreign',
		'constraint_name' => $orm->getTableName() .'_'. $key->getRealName() .'_fkey',
		'key_name' => $key->getRealName(),
		'reference_table' => $key->getReferenceTableName(),
		'reference_key_name' => $key->getReferenceTableKey(),
	);
    }

    /**
     * @param Orm $orm
     */
    public function syncTable($orm) {
	$tableInfo= $orm->getConnection()->getDatabaseInfo()->getTable($orm->getTableName());
	$sql= null;

	// checking model against db
	foreach ($orm->getFields() as $name => $field) {

	    // column exists
	    if ( $tableInfo->hasColumn($field->getRealName()) ) {
		//TODO

	    }
	    else {
		$template= $this->getTemplate('alter-table-add-column.psql' );
		$template->table= $orm;
		$template->field= $this->addField($field);
		$sql .= $this->renderTemplate($template);
	    }
	}

	// checking db against model
	foreach ( $tableInfo->getColumnNames() as $column) {

	    // column doesn't exists, needs to be dropped
	    if ( !$orm->hasField($column) ) {
		$template= $this->getTemplate('alter-table-drop-column.psql' );
		$template->table= $orm;
		$template->field= $column;
		$sql .= $this->renderTemplate($template);
	    }
	}
	

	return $sql;
   }


    public function dropTable($orm) {
	$template= $this->getTemplate('drop-table.psql' );
	$template->table= $orm;
	return $this->renderTemplate($template);
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

