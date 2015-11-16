<?php

define(PROJECTS_ROOT, DOKU_INC . '/data/projects/');

abstract class Projects_file 
{
	private static $types = array();

	private $display = '';
	private $file_path = '';
	private $pos = 0;
	private $exit_pos = 0;
	private $modified_date = '';
	private $code = NULL;

	public static function register_file_type($type, $class) {
		self::$types[$type] = $class;
	}

	public static function projects_file_path($id, $check_existence=true) {
	    $path = PROJECTS_ROOT . implode('/', explode(':', $id));
	    if ($check_existence && !file_exists($path)) return false;
	    return $path;
	}

	public static function file($id, $meta) {
		if (!is_array($meta)) return NULL;
		if (!isset($meta['type'])) return NULL;
		$type = $meta['type'];
		if (!isset(self::$types[$type])) return NULL;
		return new self::$types[$type]($id, $meta);
	}

	public function __construct($id, $meta) {
		$this->file_path = self::projects_file_path($id, false);
		if (isset($meta['display']))
			$this->display = $meta['display'];
		if (isset($meta['pos']))
			$this->pos = $meta['pos'];
		if (isset($meta['exit_pos']))
			$this->exit_pos = $meta['exit_pos'];
		if (isset($meta['modified']))
			$this->modified_date = $meta['modified'];
		if (isset($meta['code']))
			$this->code = new Projects_code($meta['code']);
	}

	public function set_exit_pos($pos) {
		$this->exit_pos = $pos;
	}

	abstract public function type();
	abstract protected function is_modified($old_meta);

	public function modified_date() { return $this->modified_date; }

	public function check_modified($old_meta) {
		$modified = $this->is_modified($old_meta);
		if (!$modified) {
			if ($this->code != NULL && isset($meta['code']))
				$modified = $this->code->is_modified($meta['code']);
			else $modified = true;
		}
		if ($modified || !isset($old['modified']))
			$this->modified_date = time();
		else $this->modified_date = $old['modified'];             
	}

	public function meta() {
		$meta = array('type' => $this->type());
		if ($this->display) $meta['display'] = $this->display;
		$meta['pos'] = $this->pos;
		$meta['exit_pos'] = $this->exit_pos;
		$meta['modified'] = $this->modified_date;
		if ($this->code) 
			$meta['code'] = $this->code->meta();
		return $meta;
	}

	public function code() { return $this->code; }
}

class Projects_file_source extends Projects_file
{
	public function __construct($id, $meta) {
		parent::__construct($id, $meta);
	}

	public function type() { return "source"; }

	public function is_modified($old_meta) {
		return FALSE;
	}
}

Projects_file::register_file_type("source", Projects_file_source);
