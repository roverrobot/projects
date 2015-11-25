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

	public function xhtml($editor_id, $do) {
        $files = array(
            PROJECTS_EDITOR_REQUIRE_JS, 
            PROJECTS_EDITOR_CODEMIRROR_CSS, 
            PROJECTS_EDITOR_CODEMIRROR_DW_CSS,
            PROJECTS_EDITOR_CODEMIRROR_JS);
        $paths = implode($files, ':');
    	$id = 'PROJECTS_CODEMIRROR_' . $editor_id;
        $highlight = $this->highlight;
        $content = "<textarea id=\"$id\" require=\"$paths\" mode=\"$highlight\" editor=\"codemirror\" name=\"$editor_id\">" . $this->code . DOKU_LF . '</textarea>' . DOKU_LF;
        if (auth_quickaclcheck($ID) < AUTH_EDIT || $this->read_only)
            return $content;

        $form = new Doku_Form(array('id' => $id . '-form', 'editor' => $editor_id));
        $form->addElement(form_makeButton('submit', $do, 'edit', array(
            'id' => "$editor_id-edit")));
        $form->addElement(form_makeButton('cancel', '', 'cancel', array('id' => $editor_id . '-cancel')));
        $form->addElement($content);
        return $form->getForm();
	}
}