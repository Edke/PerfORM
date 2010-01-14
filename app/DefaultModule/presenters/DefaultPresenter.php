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
    }


    public function actionSyncdb()
    {
	$edke= $this->getModel();

	$sql = $edke->sqlsync();
	$this->template->sql = $sql;
    }

    public function actionSqlall()
    {
	$edke= $this->getModel();

	//$sql = $edke->sqlall();
	//$this->template->sql = $sql;
    }

    public function actionSqlclear()
    {
	$edke= $this->getModel();

	//$sql = $edke->sqlall();
	//$this->template->sql = $sql;
    }


}

