<?php
/**
 * ActionPresenter
 *
 * @author kraken
 */
class Console_BasePresenter extends Presenter
{

    public function startup() {
	parent::startup();
	
        $this->template->registerHelper('fshl_highlighter', array($this, 'fshl_highlighter'));
	$this->template->registerHelper('ansi_console_colors', array($this, 'ansi_console_colors'));
	$this->template->registerHelper('wordwrap', 'wordwrap');

	//$this->template->local= Environment::getHttpRequest()->getRemoteAddress() == '127.0.0.1' ? true : false;
    }


    public function fshl_highlighter($content)
    {
	$parser = new fshlParser('ANSI_UTF8', P_TAB_INDENT);
	$content= $parser->highlightString('SQL', $content);
	return html_entity_decode($content);
    }


    public function ansi_console_colors($content)
    {
	return Console_Color::convert($content);
    }
}

