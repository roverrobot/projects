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
    protected $file = NULL;
    protected $data = array();

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
 
    abstract protected function analyze();
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if (!$data) return;

        switch ($data['command']) {
            case 'enter':
                if (!$this->file) {
                    $this->data = $data['attributes'];
                    $this->data['entertag'] = $data['tag'];
                }
                return;

            case 'code':
                if (!$this->file) {
                    $this->data['code'] = $data['code'];
                    $this->data['codepos'] = $data['pos'];
                }
                return;

            case 'exit':
                if ($this->file) break;
                global $ID;
                $this->data['exittag'] = $data['tag'];
                $this->file = Projects_file::file($ID, $this->data);
                // auto dependency
                $this->analyze();
        }

        switch ($mode) {
            case 'metadata' :
                $this->render_meta($renderer);
                break;
            case 'xhtml' :
                $this->render_xhtml($renderer);
                break;
        }
    }

    protected function render_meta(&$renderer) {
        // check if the project path exists
        $ns = getNS($this->file->id());
        $path = Projects_file::projects_file_path($ns, false);
        if (!file_exists($path)) mkdir($path, 0700, true);

        global $OLD_PROJECTS_FILE;
        $this->file->update_from($OLD_PROJECTS_FILE);
        $OLD_PROJECTS_FILE = $this->file;

        $renderer->meta['projectfile'] = $this->file->meta();
    }

    protected function createTabs() {
        global $REV;
        $date = ($REV) ? $REV : $this->file->modified_date();
        $this->tabs = new Projects_XHTMLTabs();
        $summary = new Projects_SummaryTab($this->tabs, $this->file);
        $this->tabs->newTab($summary);
        $deps = $this->file->dependency();
        $dependency = new Projects_DependencyTab($this->tabs, $deps);
        $this->tabs->newTab($dependency);
    }

    protected function render_xhtml(&$renderer) {
        $this->createTabs();
        $renderer->doc .= $this->tabs->xhtml();
    }

    protected function read_only() {
        global $ID;
        global $REV;
        return $REV || (auth_quickaclcheck($ID) < AUTH_EDIT);
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
