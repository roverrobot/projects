<?php
/**
 * The base class of syntax plugin to handle <source-file> and 
 * <generated-file> etc tags
 *
 */

require_once DOKU_PLUGIN . 'syntax.php';
require_once dirname(__FILE__) . '/../../lib/project/file.php';
require_once dirname(__FILE__) . '/../../lib/syntax/xhtml.php';

abstract class syntax_projectfile extends DokuWiki_Syntax_Plugin
{
    abstract protected function type();

    protected $project_file = NULL;

    protected function tag() { return $this->type() . '-file'; } 
 
    function getType() { 
        return 'container';
    }
        
    function getPType() { 
        return 'stack';
    }
        
    function getSort() { 
        return 1; 
    }
    
    protected function mode() {
        return 'plugin_projects_' . $this->type();
    }

    function connectTo($mode) {
        $enter_tag = '<' . $this->tag() . '\b.*?>';
        $exit_tag = '</' . $this->tag() . '>';
        $this->Lexer->addEntryPattern($enter_tag . '(?=.*' . $exit_tag . ')'
            , $mode, $this->mode()); 
    }

    function postConnect() {
        $exit_tag = '</' . $this->tag() . '>';
        $this->Lexer->addExitPattern($exit_tag, $this->mode()); 
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
        global $ID;
        global $PROJECTS_FILE_OCCURRENCE;
        if (!isset($PROJECTS_FILE_UNIQUE)) $PROJECTS_FILE_UNIQUE = '';

        // guarantee that the <source-file> and <generated-file> tags can only appear once.
        if ($PROJECTS_FILE_OCCURRENCE == $ID)
            return array('command' => 'text', 'text' => $match, 'pos' => $pos);

        switch ($state) {
            case DOKU_LEXER_ENTER:
                $attr = $this->parse($match);
                $attr['type'] = $this->type();
                $attr['pos'] = $pos;
                return array(
                    'command' => 'enter',
                    'attributes' => $attr);
            case DOKU_LEXER_EXIT:
                $PROJECTS_FILE_OCCURRENCE = $ID;
                return array('command' => 'exit', 'pos' => $pos);
            case DOKU_LEXER_UNMATCHED:
                return array('command' => 'text', 'text' => $match, 'pos' => $pos);
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
                $this->project_file = Projects_file::file($ID, $data['attributes']);
                // check if the project path exists
                $ns = getNS($ID);
                $path = Projects_file::projects_file_path($ns, false);
                if (!file_exists($path)) mkdir($path, 0700, true);
                break;

            case 'exit':
                $this->project_file->set_exit_pos($data['pos']);

                if (isset($renderer->meta['projectfile']))
                    $old = $renderer->meta['projectfile'];
                else $old = array();
                $this->project_file->check_modified($old);

                $renderer->persistent['projectfile'] = $this->project_file->meta();
                $renderer->meta['projectfile'] = $renderer->persistent['projectfile'];

                break;
        }
    }

    protected function actions($meta) {
        global $ID;
        return array(
            'download' => download_button($ID),
            'delete' => delete_button($ID),
            'manage files' => manage_files_button($ID));
    }

    protected function xhtml_tabs($file) {
        global $ID;
        $format = 'D M d, Y \a\t g:i:s a';
        if (date_default_timezone_get() == 'UTC') $format .= ' e';
        $updated = date($format, $file->modified_date());
        $type = $file->type();

        $actions = $this->actions($meta);
        $xhtml_actions = '<li>Actions: ' . implode(', ', $actions) . '</li>'.DOKU_LF;

        return array(
            'Summary' => '<ul>' . DOKU_LF .
                "<li>" . html_wikilink($ID) . 
                ": $type file, last updated on $updated</li>" . DOKU_LF .
                $xhtml_actions .
                '</ul>' . DOKU_LF);
    }

    protected function render_xhtml(&$renderer, $data) {
        switch ($data['command']) {
            case 'enter':
                global $ID;
                global $INFO;
                $meta = $INFO['meta']['projectfile'];
                $file = Projects_file::file($ID, $meta);

                $tabs = $this->xhtml_tabs($file);
                $ul = '<ul>';
                $panels = '';
                $i = 0;
                foreach ($tabs as $title => $tab) {
                    $ul .= '<li><a href="#projects_file_tabs_' . $i . '">' .
                        $title . '</a></li>' . DOKU_LF;
                    $panels .= '<div id="projects_file_tabs_' . $i . '">' . DOKU_LF .
                        $tab . '</div>' . DOKU_LF;
                    $i++;
                }
                $ul .= '</ul>' . DOKU_LF;
                $renderer->doc .= '<div id="projects_file_tabs">' .
                    $ul . $panels . '</div>' . DOKU_LF; 

                break;
            case 'exit':
                $renderer->doc .= '</dl>';
                break;
            case 'text':
                $renderer->doc .= htmlspecialchars($data['text']);
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
