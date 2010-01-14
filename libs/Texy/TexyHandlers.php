<?php

final class TexyHandlers {

    /**
     * User handler for code block
     *
     * @param TexyHandlerInvocation  handler invocation
     * @param string  block type
     * @param string  text to highlight
     * @param string  language
     * @param TexyModifier modifier
     * @return TexyHtml
     */
    public static function blockHandler($invocation, $blocktype, $content, $lang, $modifier) {
	if ($blocktype !== 'block/code') {
		return $invocation->proceed();
	}

	$lang = strtoupper($lang);
	if ($lang == 'JAVASCRIPT') $lang = 'JS';

	$parser = new fshlParser('HTML_UTF8', P_TAB_INDENT);
	if (!$parser->isLanguage($lang)) {
		return $invocation->proceed();
	}

	$texy = $invocation->getTexy();
	$content = Texy::outdent($content);
	$content = $parser->highlightString($lang, $content);
	$content = $texy->protect($content, Texy::CONTENT_BLOCK);

	$elPre = TexyHtml::el('pre');
	if ($modifier) $modifier->decorate($texy, $elPre);
	$elPre->attrs['class'] = strtolower($lang);

	$elCode = $elPre->create('code', $content);

	return $elPre;
    }
}


