<?php
/**
 * The syntax plugin to handle <project-file> tags
 *
 */

require_once dirname(__FILE__) . '/../lib/syntax/code.php';

global $PARSER_MODES;
$PARSER_MODES['projectfile_content_tags'] = array('plugin_projects_content');

class syntax_plugin_projects_content extends syntax_projects_code {
 
    function getType() { 
        return 'projectfile_content_tags';
    }

    protected function tag() { return "content"; }
}

