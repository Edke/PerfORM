<?php
/**
 * ActionPresenter
 *
 * @author kraken
 */
class Console_SqlallPresenter extends Console_BasePresenter
{

    public function actionDefault()
    {
	$sql= PerfORMController::sqlall();
	$this->template->sql = (is_null($sql)) ? false : $sql;
    }
}

