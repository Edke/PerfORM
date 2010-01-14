<?php
/**
 * Description of DefaultPresenter
 *
 * @author kraken
 */
class Default_BasePresenter extends Presenter {

    public function startup() {

	$texy = new Texy();
	$texy->addHandler('block', array("TexyHandlers", 'blockHandler'));

        $this->template->registerHelper('texy', array($texy, 'process'));

	// processing
//	$text = file_get_contents('sample.texy');
//	$html = $texy->process($text);  // that's all folks!

//	echo '<style type="text/css">'. file_get_contents('fshl/styles/COHEN_style.css') . '</style>';



    }

}



