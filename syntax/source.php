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

    protected function analyze() {
        $deps = Projects_Analyzer::auto_dependency($this->file);
        foreach ($deps as $dep)
            $this->file->set_dependence($dep, TRUE);
    }

    protected function createTabs() {
        parent::createTabs();
    	$editor = Projects_editor::editor($this->file->id(), $this->file->code(), $this->file->highlight());
    	$editor->read_only = $this->read_only();
    	$content = $editor->xhtml('content', 'savecontent');
        $summary = $this->tabs->tab('Summary');
        $summary->setContent($content);
    }
}
