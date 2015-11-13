<?php

define(PROJECTS_ROOT, DOKU_INC . '/data/projects/');

abstract class Projects_file 
{
	private static $types = array();

	private $display = '';
	private $file_path = '';
	private $pos = -1;
	private $exit_pos = -1;
	private $modified_date = '';

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
		$file_path = self::projects_file_path($id, false);
		if (isset($meta['display']))
			$this->$dependencies = $meta['display'];
		if (isset($meta['pos']))
			$this->$pos = $meta['pos'];
	}

	public function set_exit_pos($pos) {
		$this->exit_pos = $pos;
	}

	abstract protected function type();
	abstract protected function is_modified($old_meta);
	public function check_modified($old_meta) {
		$modified = $this->is_modified($old_meta);
		if ($modified || !isset($old['modified']))
			$this->modified_date = microtime(true);
		else $this->modified_date = $old['modified'];                    
	}

	public function meta() {
		$meta = array('type' => $this->type());
		if ($this->display) $meta['display'] = $this->display;
		if ($this->pos >= 0) $meta['pos'] = $this->pos;
		if ($this->exit_pos >= 0) $meta['exit_pos'] = $this->exit_pos;
		if ($this->modified_date) $meta['modified'] = $this->modified_date;
		return $meta;
	}
}

class Projects_file_source extends Projects_file
{
	public function __construct($id, $meta) {
		parent::__construct($id, $meta);
	}

	protected function type() { return "source"; }

	public function is_modified($old_meta) {
		return FALSE;
	}
}

Projects_file::register_file_type("source", Projects_file_source);
