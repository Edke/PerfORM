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


	Debug::consoleDump(dibi::getDatabaseInfo()->getTableNames());



	$edke->sqlsync();
	
	
    }
}



