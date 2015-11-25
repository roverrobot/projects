<?php

define(PROJECTS_ROOT, DOKU_INC . '/data/projects/');
require_once(dirname(__FILE__) . '/code.php');

abstract class Projects_file 
{
	private static $types = array();

	private $display = '';
	private $pos = 0;
	private $exit_pos = 0;
	private $modified_date = '';
	protected $file_path = '';
	protected $code = NULL;

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
	abstract protected function modify_file();

	public function modified_date() { return $this->modified_date; }

	public function is_modified($old_meta) {
		if ($this->type != $old_meta['type']) return TRUE;
		if ($this->code != NULL && isset($old_meta['code']) &&
			$this->code->is_modified($old_meta['code']))
			return TRUE;
		return FALSE;
	}

	public function modify() {
		$this->modified_date = time();
		$this->modify_file();
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
	public function file_path() { return $this->file_path; }
}

class Projects_file_source extends Projects_file
{
	public function __construct($id, $meta) {
		parent::__construct($id, $meta);
	}

	public function type() { return "source"; }

	public function modify_file() {
		if (file_exists($this->file_path)) {
			$content = file_get_contents($this->file_path);
			if ($content == $this->code->code()) return;
		}
		file_put_contents($this->file_path, $this->code->code());
	}
}

Projects_file::register_file_type("source", Projects_file_source);
