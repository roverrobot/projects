<?php

//define(PROJECTS_EDITOR_CODEMIRROR_CSS, dirname(__FILE__) . '/codemirror.css');
define(PROJECTS_EDITOR_CODEMIRROR_JS, DOKU_REL . 'lib/plugins/projects/editor/codemirror/codemirror-compressed.js');
define(PROJECTS_EDITOR_CODEMIRROR_ADDONS_JS, DOKU_REL . 'lib/plugins/projects/editor/codemirror/codemirror-addons-compressed.js');
define(PROJECTS_EDITOR_CODEMIRROR_MODES_JS, DOKU_REL . 'lib/plugins/projects/editor/codemirror/codemirror-modes-compressed.js');
define(PROJECTS_EDITOR_CODEMIRROR_CSS, DOKU_REL . 'lib/plugins/projects/editor/codemirror/codemirror.css');
define(PROJECTS_EDITOR_CODEMIRROR_DW_CSS, DOKU_REL . 'lib/plugins/projects/editor/codemirror/codemirror-dokuwiki.css');

class Projects_editor_CodeMirror extends Projects_editor {
    private static $written = FALSE;

	public static function name() { return 'CodeMirror'; }
    public function get_highlight($file, $code) { 
        return '';
    }

	public function xhtml($editor_id) {
        if (!self::$written) {
            self::$written = TRUE;
            $xhtml = 
                '<script src="'. PROJECTS_EDITOR_CODEMIRROR_JS .'"></script>' . DOKU_LF .
                '<script src="'. PROJECTS_EDITOR_CODEMIRROR_ADDONS_JS .'"></script>' . DOKU_LF .
                '<script src="'. PROJECTS_EDITOR_CODEMIRROR_MODES_JS .'"></script>' . DOKU_LF .
                '<link rel="stylesheet" type="text/css" href="' . PROJECTS_EDITOR_CODEMIRROR_CSS . '"/>' . DOKU_LF .
                '<link rel="stylesheet" type="text/css" href="' . PROJECTS_EDITOR_CODEMIRROR_DW_CSS . '"/>' . DOKU_LF;
        } else $xhtml = '';
    	$id = 'PROJECTS_CODEMIRROR_' . $editor_id;
        $opts = 'lineNumbers: true, mode: "' . $this->highlight . '"';
        if ($this->read_only) $opts = $opts . ', readOnly: true';
    	return $xhtml . "<textarea id=\"$id\">" . $this->code . '</textarea>' . DOKU_LF .
    		'<script> var editor = CodeMirror.fromTextArea(document.getElementById("' . $id . 
            '"), {' . $opts . '}); </script>' . DOKU_LF; 
	}
}