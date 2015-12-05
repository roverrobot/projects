<?php

class Projects_formatter_image extends Projects_formatter {
	
	public function can_handle($mimetype) {
		return ($mimetype && substr($mimetype, 0, 5) == 'image/');
	}

    public function format($file) {
    	return '<a href="' . ml($file->id()) . '">' . $file->id() . '</a>';
    }

}