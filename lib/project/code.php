<?php

class Projects_code {
	private $code = '';
	private $pos = 0;
	private $exit_pos = 0;
	private $code_pos = 0;
	private $highlight = '';

	public function __construct($meta) {
		if (isset($meta['code']))
			$this->code = $meta['code'];
		if (isset($meta['pos']))
			$this->pos = $meta['pos'];		
		if (isset($meta['exit_pos']))
			$this->exit_pos = $meta['exit_pos'];		
		if (isset($meta['code_pos']))
			$this->exit_pos = $meta['code_pos'];		
		if (isset($meta['highlight']))
			$this->highlight = $meta['highlight'];		
	}

	public function code() { return $this->code; }
	public function pos() { return $this->pos; }
	public function exit_pos() { return $this->exit_pos; }
	public function code_pos() { return $this->code_pos; }
	public function highlight() { return $this->highlight; }
	
	public function meta() {
		$meta = array('code' => $this->code);
		if ($this->pos) 
			$meta = array('pos' => $this->pos);
		if ($this->exit_pos) 
			$meta = array('exit_pos' => $this->exit_pos);
		if ($this->highlight) 
			$meta = array('highlight' => $this->highlight);
		return $meta; 
	}

	public function is_modified($old_meta) {
		$old = new Projects_code($old_meta);
		return ($old->code() != $this->code);
	}
}