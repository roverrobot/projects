<?php

class Projects_formatter_image extends Projects_formatter {
	
	public function can_handle($mimetype) {
		return ($mimetype && substr($mimetype, 0, 6) == 'image/');
	}

    public function format($file) {
    	return '<image src="' . ml($file->id()) . '">' . $file->id() . '</image>';
    }

}