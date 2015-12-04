<?php
/**
 * The syntax plugin to handle <source-file> tags
 *
 */

require_once dirname(__FILE__) . '/../lib/syntax/file.php';
require_once dirname(__FILE__) . '/../lib/editor.php';

class syntax_plugin_projects_generated extends syntax_projectfile
{
    protected function type() { return 'generated'; }

    protected function analyze() {
    }

    protected function createTabs() {
        parent::createTabs();
        $summary = $this->tabs->tab('Summary');
        $summary->setContent('<p></p>');
        $recipe = new Projects_RecipeTab($this->tabs, $this->file, $this->read_only());
        $this->tabs->newTab($recipe);
    }

}
