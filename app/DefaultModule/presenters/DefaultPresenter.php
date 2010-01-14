<?php
/**
 * BasePresenter
 *
 * @author kraken
 */
class Default_DefaultPresenter extends Default_BasePresenter {

    public function getModel() {
	$edke = new Person;
	$edke->name= 'Edke';
	return $edke;
    }

    public function actionDefault()
    {
	$edke= $this->getModel();

	$edke->gender= 'm';
	$edke->age= 33;
	$edke->save();

	$this->template->sql= dibi::$sql;
    }


    public function actionSyncdb()
    {
	$edke= $this->getModel();

	$sql = $edke->operationSyncdb();
	$this->template->sql = $sql;
    }

    public function actionSqlall()
    {
	$edke= $this->getModel();

	$sql = $edke->operationSqlall();
	$this->template->sql = $sql;
    }

    public function actionSqlclear()
    {
	$edke= $this->getModel();

	$sql = $edke->operationSqlclear();
	$this->template->sql = $sql;
    }

}

