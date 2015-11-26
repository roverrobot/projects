<?php

define(PROJECTS_ROOT, DOKU_INC . '/data/projects/');

abstract class Projects_file 
{
	private static $types = array();

	private $display = '';
	private $highlight = '';
	private $entertag = array();
	private $exittag = array();
	private $pos = array();
	private $modified_date = '';
	protected $file_path = '';
	protected $code = '';

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
		if (isset($meta['highligh']))
			$this->highlight = $meta['highlight'];
		if (isset($meta['entertag']))
			$this->entertag = $meta['entertag'];
		if (isset($meta['pos']))
			$this->pos = $meta['pos'];
		if (isset($meta['exittag']))
			$this->exittag = $meta['exittag'];
		if (isset($meta['modified']))
			$this->modified_date = $meta['modified'];
		if (isset($meta['code']))
			$this->code = $meta['code'];
	}

	public function set_exit_pos($pos) {
		$this->exit_pos = $pos;
	}

	abstract public function type();
	abstract protected function modify_file();

	public function modified_date() { return $this->modified_date; }

	public function is_modified($old_meta) {
		if ($this->type() != $old_meta['type']) return TRUE;
		if ($this->code && !isset($old_meta['code'])) return FALSE;
		if ($this->code != $old_meta['code']) return TRUE;
		$this->modified_date = $old_meta['modified'];
	}

	public function modify() {
		$this->modified_date = time();
		$this->modify_file();
	}

	public function meta() {
		$meta = array('type' => $this->type());
		if ($this->display) $meta['display'] = $this->display;
		if ($this->highlight) $meta['highlight'] = $this->highlight;
		$meta['pos'] = $this->pos;
		$meta['exittag'] = $this->exittag;
		$meta['entertag'] = $this->entertag;
		$meta['modified'] = $this->modified_date;
		$meta['code'] = $this->code;
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
			if ($content == $this->code) return;
		}
		file_put_contents($this->file_path, $this->code);
	}
}

Projects_file::register_file_type("source", Projects_file_source);
