<?php

class Projects_formatter_text extends Projects_formatter {
	public function can_handle($mimetype) {
		return ($mimetype && substr($mimetype, 0, 5) == 'text/');
	}

    public function format($file) {
    	$editor = Projects_Editor_Manager::manager()->editor(
    		$file->id(), $file->content(), '');
    	$editor->read_only = TRUE;
    	return $editor->xhtml('content', 'show');
    }

}