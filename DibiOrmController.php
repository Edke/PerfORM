<?php
/**
 * controler
 *
 * @author kraken
 */
class DibiOrmController extends DibiOrmBase
{
    protected $models= array();

    public function  __construct()
    {
	$robot = new DibiOrmModelLoader();
	$robot->addDirectory(APP_DIR);
	$this->models= $robot->getModels();
    }

    public function syncdb($confirm = false)
    {
	$sql= null;
	foreach( $this->models as $model) {

	    if ( $model->getConnection()->getDatabaseInfo()->hasTable($model->getTableName()) ) {
		$sql .= $model->getDriver()->syncTable($model);
	    }
	    else {
		$sql .= $model->getDriver()->createTable($model);
	    }
	}

	if ( !is_null($sql) && $confirm ) {
	    $this->execute($sql);
	}
	return $sql;
    }

    public function sqlall()
    {
	$sql= null;
	foreach( $this->models as $model) {
	    $sql .= $model->getDriver()->createTable($model);
	}
	return $sql;
    }
    
    public function sqlclear($confirm)
    {
	$sql= null;
	foreach( $this->models as $model) {
	    if ( $model->getConnection()->getDatabaseInfo()->hasTable($model->getTableName()) ) {
		$sql .= $model->getDriver()->dropTable($model);
	    }
	}
	if ( !is_null($sql) && $confirm ) {
	    $this->execute($sql);
	}
	return $sql;
    }

    protected function execute($sql) {
	$this->getConnection()->begin();
	$this->getConnection()->query($sql);
	$this->getConnection()->commit();
    }
}