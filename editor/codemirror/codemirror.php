<?php

define(PROJECTS_EDITOR_REQUIRE_JS, DOKU_REL . 'lib/plugins/projects/editor/require.js');
define(PROJECTS_EDITOR_CODEMIRROR_PATH, DOKU_REL . 'lib/plugins/projects/editor/codemirror');
define(PROJECTS_EDITOR_CODEMIRROR_JS, PROJECTS_EDITOR_CODEMIRROR_PATH . '/codemirror.js');
define(PROJECTS_EDITOR_CODEMIRROR_CSS, PROJECTS_EDITOR_CODEMIRROR_PATH . '/codemirror-5.8/lib/codemirror.css');
define(PROJECTS_EDITOR_CODEMIRROR_DW_CSS, PROJECTS_EDITOR_CODEMIRROR_PATH . '/codemirror-dokuwiki.css');

class Projects_editor_CodeMirror extends Projects_editor {
	public static function name() { return 'CodeMirror'; }
    public function get_highlight($file, $code) { 
        return '';
    }

	protected function editor_xhtml($editor_id, $do) {
        $files = array(
            PROJECTS_EDITOR_CODEMIRROR_CSS, 
            PROJECTS_EDITOR_CODEMIRROR_DW_CSS,
            PROJECTS_EDITOR_CODEMIRROR_JS);
        $paths = implode($files, ':');
        $highlight = $this->highlight;
        return "<textarea class=\"PROJECTS_EDITOR_CODEMIRROR\" 
            id=\"$editor_id\" 
            require=\"$paths\" 
            editor=\"codemirror\" 
            mode=\"$highlight\">" . 
            htmlspecialchars($this->code) . DOKU_LF .
            '</textarea>';
	}
}