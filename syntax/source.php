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

    public function getAllowedTypes() {
        return array('projectfile_content_tags');
    }

    protected function xhtml_content($file) {
    	global $ID;
    	$code = $file->code();
    	if (!$code) return '';

    	return '<pre>' . $code->code() . '</pre>' . DOKU_LF;
    }
}
