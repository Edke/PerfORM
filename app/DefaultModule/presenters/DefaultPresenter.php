<?php
/**
 * BasePresenter
 *
 * @author kraken
 */
class Default_DefaultPresenter extends Default_BasePresenter {

    public function actionDefault()
    {

	$edke = new Person;
	$edke->name= 'Edke';
	//$edke->save();

	$this->template->sql = $edke->sqlsync();
    }
}

