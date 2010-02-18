<?php
/**
 * ActionPresenter
 *
 * @author kraken
 */
class Console_SqlclearPresenter extends Console_BasePresenter
{

    public function actionDefault()
    {
	$confirm= $this->getParam('confirm');
	$execute= ($confirm) ? true : false;
	$sql= PerfORMController::sqlclear($execute);
	$this->template->sql = (is_null($sql)) ? false : $sql;
	$this->template->confirm= $execute;
    }
}

