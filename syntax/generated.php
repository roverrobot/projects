<?php
/**
 * The syntax plugin to handle <source-file> tags
 *
 */

require_once dirname(__FILE__) . '/../lib/syntax/file.php';
require_once dirname(__FILE__) . '/../lib/editor.php';
require_once dirname(__FILE__) . '/../lib/formatter.php';

class syntax_plugin_projects_generated extends syntax_projectfile
{
    protected function type() { return 'generated'; }

    protected function content($file) {
        if ($file->status() === PROJECTS_MODIFIED)
            return '<div>The file is not generated yet: ' . make_button($file->id(), FALSE) . '</div>'; 
        $content = Projects_formatter::xhtml($file);
        return $content;
    }

    protected function createTabs($file) {
        parent::createTabs($file);
        $summary = $this->tabs->tab('Summary');
        if ($file->is_making())
            $summary->newAction(kill_button($file->id()));
        else $summary->newAction(make_button($file->id(), $file->status() == PROJECTS_MADE));
        $summary->setContent($this->content($file));
        $recipe = new Projects_RecipeTab($this->tabs, $file, $this->read_only());
        $this->tabs->newTab($recipe);
        $log = new Projects_LogTab($this->tabs, $file);
        $this->tabs->newTab($log);
    }

}
