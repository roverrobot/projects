<?php
/**
 * The base class of syntax plugin to handle <source-file> and 
 * <generated-file> etc tags
 *
 */

require_once DOKU_PLUGIN . 'syntax.php';
require_once dirname(__FILE__) . '/../../lib/project/file.php';
require_once dirname(__FILE__) . '/../../lib/syntax/xhtmltab.php';

abstract class syntax_projectfile extends DokuWiki_Syntax_Plugin
{
    abstract protected function type();

    protected $tabs = array();
    protected $highlight = '';

    protected function tag() { return $this->type() . '-file'; } 
 
    function getPType() { 
        return 'normal';
    }
        
    function getType() { 
        return 'disabled';
    }
        
    function getSort() { 
        return 1; 
    }
    
    protected function mode() {
        return 'plugin_projects_' . $this->type();
    }

    protected function enterTag() {
        return '<' . $this->tag() . '\b.*?>';
    }
    
    protected function exitTag() {
        return '</' . $this->tag() . '>';
    }
    
    function connectTo($mode) {
        $this->Lexer->addEntryPattern($this->enterTag() . '(?=.*' . $this->exitTag() . ')'
            , $mode, $this->mode()); 
    }

    function postConnect() {
        $this->Lexer->addExitPattern($this->exitTag(), $this->mode()); 
    }

    private function parse($tag) {
        $xml = DOMDocument::loadXML($tag . $this->exitTag());
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
        global $ID;
        global $PROJECTS_FILE_OCCURRENCE;
        if (!isset($PROJECTS_FILE_UNIQUE)) $PROJECTS_FILE_UNIQUE = '';

        // guarantee that the <source-file> and <generated-file> tags can only appear once.
        if ($PROJECTS_FILE_OCCURRENCE == $ID)
            return array('command' => 'text', 'text' => $match, 'pos' => $pos);

        $tag = array('pos' => $pos, 'length' => strlen($match));
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $attr = $this->parse($match);
                $attr['type'] = $this->type();
                $attr['pos'] = $pos;
                $attr['length'] = $length;
                return array(
                    'command' => 'enter',
                    'attributes' => $attr,
                    'tag' => $tag);
            case DOKU_LEXER_EXIT:
                $PROJECTS_FILE_OCCURRENCE = $ID;
                return array('command' => 'exit', 'tag' => $tag);
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
                    'pos' => $tag);
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
        global $ID;
        switch ($data['command']) {
            case 'enter':
                $renderer->persistent['projectfile'] = $data['attributes'];
                $renderer->persistent['projectfile']['entertag'] = $data['tag'];
                break;

            case 'code':
                $renderer->persistent['projectfile']['code'] = $data['code'];
                $renderer->persistent['projectfile']['codepos'] = $data['pos'];
                break;

            case 'exit':
                $renderer->persistent['projectfile']['exittag'] = $data['tag'];
                $project_file = Projects_file::file($ID, $renderer->persistent['projectfile']);
                // check if the project path exists
                $ns = getNS($ID);
                $path = Projects_file::projects_file_path($ns, false);
                if (!file_exists($path)) mkdir($path, 0700, true);
                $project_file->set_exit_pos($data['pos']);

                if (isset($renderer->meta['projectfile']))
                    $old = $renderer->meta['projectfile'];
                else $old = array();
                $project_file->update_from($old);

                $renderer->persistent['projectfile'] = $project_file->meta();
                $renderer->meta['projectfile'] = $renderer->persistent['projectfile'];

                break;
        }
    }

    abstract protected function xhtml_code($highlight, $code);

    protected function render_xhtml(&$renderer, $data) {
        switch ($data['command']) {
            case 'enter':
                global $INFO;
                $date = Projects_file::getDateFromMeta($INFO['meta']['projectfile'], 'modified');
                if (isset($data["attributes"]["highlight"]))
                    $this->highlight = $data["attributes"]["highlight"];
                $this->tabs = new Projects_XHTMLTabs();
                $summary = new Projects_SummaryTab($this->tabs, $data["attributes"]);
                $summary->setUpdate($date);
                $this->tabs->newTab($summary);
                $dependency = new Projects_DependencyTab($this->tabs, $data["attributes"]);
                $this->tabs->newTab($dependency);
                break;

            case 'exit':
                $renderer->doc .= $this->tabs->xhtml();
                break;

            case 'code':
                $this->xhtml_code($this->highlight, $data['code']);
                break;
        }
    }
    
}

#check whether PROJECTS_ROOT exists
global $PROJECTS_INITIALIED;
if (!isset($PROJECTS_INITIALIED)) {
    if (!file_exists(PROJECTS_ROOT)) 
        mkdir(PROJECTS_ROOT);
    elseif (!is_dir(PROJECTS_ROOT)) {
        @unlink(PROJECTS_ROOT);
        mkdir(PROJECTS_ROOT);    
    }
    $PROJECTS_INITIALIED = true;
}
