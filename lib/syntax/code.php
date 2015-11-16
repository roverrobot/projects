<?php
/**
 * The syntax plugin to handle <project-file> tags
 *
 */

require_once DOKU_PLUGIN . 'syntax.php';
require_once dirname(__FILE__) . '/../project/code.php';

abstract class syntax_projects_code extends DokuWiki_Syntax_Plugin {
 
    function getPType() { 
        return 'normal';
    }
        
    function getSort() { 
        return 1; 
    }

    abstract protected function tag();

    function connectTo($mode) {
        $tag = $this->tag();
        $this->Lexer->addEntryPattern("<$tag\\b.*?>(?=.*</$tag>)",
            $mode, 'plugin_projects_' . $tag);
        $this->Lexer->addExitPattern("</$tag>", 'plugin_projects_' . $tag); 
    }

    private function parse($tag) {
        $xml = DOMDocument::loadXML($tag . '</' . $this->tag() . '>');
        if ($xml == false) return NULL;
        $attributes = array();
        foreach ($xml->firstChild->attributes as $attribute)
            $attributes[$attribute->name] = $attribute->value;
        return $attributes;
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        global $PROJECTS_CODE_OCCURENCE;
        global $ID;
        if (!isset($PROJECTS_CODE_OCCURENCE)) $PROJECTS_CODE_OCCURENCE = '';

        if ($PROJECTS_CODE_OCCURENCE == $ID)
            return array('command' => 'text', 'text' => $match, 'pos' => $pos);

        switch ($state) {
            case DOKU_LEXER_ENTER:
                $attr = $this->parse($match);
                return array(
                    'command' => 'enter',
                    'attributes' => $attr, 
                    'pos' => $pos,
                    'length' => strlen($match));
            case DOKU_LEXER_EXIT:
                $PROJECTS_CODE_OCCURENCE = $ID;
                return array(
                    'command' => 'exit',
                    'pos' => $pos,
                    'length' => strlen($match));
            case DOKU_LEXER_UNMATCHED:
                if ($match[0] == "\r") {
                    $match = substr($match, 1);
                    $pos++;
                }
                if ($match[0] == "\n") {
                    $match = substr($match, 1);
                    $pos++;
                }
                if (substr($match, -1) == "\n")
                    $match = substr($match, 0, strlen($match)-1);
                if (substr($match, -1) == "\r")
                    $match = substr($match, 0, strlen($match)-1);

                return array(
                    'command' => 'code',
                    'code' => $match,
                    'pos' => $pos,
                    'length' => strlen($match));
        }
        return false;
    }
 
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if (!$data) return;
        switch ($mode) {
            case 'metadata' :
                $this->render_meta($renderer, $data);
                break;
            case 'xhtml' :
                $this->render_xhtml($renderer, $data);
                break;
        }
    }

    protected function render_meta(&$renderer, $data) {
        switch ($data['command']) {
            case 'enter':
                $attr = $data['attributes'];
                $attr['pos'] = $data['pos'];
                $attr['code'] = '';
                $renderer->persistent['projectfile']['code'] = $attr;
                break;

            case 'code':
                $tag = $this->tag();
                $renderer->persistent['projectfile']['code']['code'] = $data['code'];
                $pos = $data['pos'] . '-' . ($data['pos']+$data['length']);
                $renderer->persistent['projectfile']['code']['code pos'] = $pos; 
                break;

            case 'exit':
                $renderer->persistent['projectfile']['code']['exit_pos'] = $data['pos'];
                break;
        }
    }

    protected function render_xhtml(&$renderer, $data) { }
}

