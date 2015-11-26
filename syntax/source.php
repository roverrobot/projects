<?php
/**
 * The syntax plugin to handle <source-file> tags
 *
 */

require_once dirname(__FILE__) . '/../lib/syntax/file.php';
require_once dirname(__FILE__) . '/../lib/editor.php';

class syntax_plugin_projects_source extends syntax_projectfile
{
    protected function type() { return 'source'; }

    protected function xhtml_code($highlight, $code) {
    	global $ID;
        global $REV;
    	$editor = Projects_editor::editor($ID, $code, $highlight);
    	$editor->read_only = $REV || (auth_quickaclcheck($ID) < AUTH_EDIT);
    	$content = $editor->xhtml('content', 'savecontent');
        $summary = $this->tabs->tab('Summary');
        $summary->setContent($content);
    }
}
