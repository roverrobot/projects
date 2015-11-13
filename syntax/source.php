<?php
/**
 * The syntax plugin to handle <source-file> tags
 *
 */

require_once dirname(__FILE__) . '/../lib/syntax/file.php';
//require_once dirname(__FILE__) . '/../lib/analyzer.php';

class syntax_plugin_projects_source extends syntax_projectfile
{
    protected function type() { return 'source'; }
}
