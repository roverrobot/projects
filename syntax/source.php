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

    protected function createTabs($file) {
        parent::createTabs($file);
    	$editor = Projects_editor::editor($file->id(), $file->code(), $file->highlight());
    	$editor->read_only = $this->read_only();
    	$content = $editor->xhtml('content', 'savecontent');
        $summary = $this->tabs->tab('Summary');
        $summary->setContent($content);
    }
}
