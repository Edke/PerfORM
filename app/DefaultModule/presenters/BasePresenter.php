<?php
/**
 * Description of DefaultPresenter
 *
 * @author kraken
 */
class Default_BasePresenter extends Presenter {

    public function startup() {
	parent::startup();
	
	$texy = new Texy();
	$texy->addHandler('block', array("TexyHandlers", 'blockHandler'));

        $this->template->registerHelper('texy', array($texy, 'process'));
    }
}



