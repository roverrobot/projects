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

    protected function content() {
        $content = Projects_formatter::xhtml($this->file);
        return $content;
    }

    protected function createTabs($file) {
        parent::createTabs($file);
        $summary = $this->tabs->tab('Summary');
        $summary->setContent($this->content());
        $recipe = new Projects_RecipeTab($this->tabs, $file, $this->read_only());
        $this->tabs->newTab($recipe);
    }

}
