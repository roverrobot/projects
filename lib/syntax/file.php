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

        switch ($data['command']) {
            case 'enter':
                $renderer->meta['projectfile'] = $data['attributes'];
                $renderer->meta['projectfile']['entertag'] = $data['tag'];
                return;

            case 'code':
                $renderer->meta['projectfile']['code'] = $data['code'];
                $renderer->meta['projectfile']['codepos'] = $data['pos'];
                return;

            case 'exit':
                global $ID;
                $renderer->meta['projectfile']['exittag'] = $data['tag'];
        }

        switch ($mode) {
            case 'metadata' :
                $this->render_meta($renderer, $file);
                break;
            case 'xhtml' :
                $file = Projects_file::file($ID);
                if ($file->status() != PROJECTS_MADE) $renderer->info['cache'] = FALSE;
                $this->render_xhtml($renderer, $file);
                break;
        }
    }

    protected function render_meta(&$renderer, $file) {
        // check if the project path exists
        global $ID;
        $ns = getNS($ID);
        $path = Projects_file::projects_file_path($ns, false);
        if (!file_exists($path)) mkdir($path, 0700, true);

        $file = Projects_file::file($ID, $renderer->meta['projectfile']);
        // auto dependency
        $file->analyze();
        $old = Projects_file::file($ID);
        $file->update_from($old);

        unset($renderer->meta['projectfile']);
        global $PROJECT_FILES;
        if (!isset($PROJECT_FILES)) $PROJECT_FILES = array($ID=>$file);
        else $PROJECT_FILES[$ID] = $file;
    }

    protected function createTabs($file) {
        global $REV;
        $date = ($REV) ? $REV : $file->modified_date();
        $this->tabs = new Projects_XHTMLTabs();
        $summary = new Projects_SummaryTab($this->tabs, $file);
        $this->tabs->newTab($summary);
        $deps = $file->dependency();
        $dependency = new Projects_DependencyTab($this->tabs, $deps);
        $this->tabs->newTab($dependency);
    }

    protected function render_xhtml(&$renderer, $file) {
        $this->createTabs($file);
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
