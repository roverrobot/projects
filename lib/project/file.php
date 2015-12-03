<?php

define(PROJECTS_ROOT, DOKU_INC . '/data/projects/');

abstract class Projects_file 
{
	private static $types = array();

	protected $id = '';
	protected $file_extension = '';
	protected $mimetype = '';
	private $display = '';
	private $highlight = '';
	private $entertag = array();
	private $exittag = array();
	private $pos = array();
	private $modified_date = 0;
	protected $file_path = '';
	protected $code = '';
	protected $dependency = array();

	public static function register_file_type($type, $class) {
		self::$types[$type] = $class;
	}

	public static function projects_file_path($id, $check_existence=true) {
	    $path = PROJECTS_ROOT . implode('/', explode(':', $id));
	    if ($check_existence && !file_exists($path)) return false;
	    return $path;
	}

	public static function file($id, $meta=NULL) {
		if ($meta == NULL) {
			global $ID;
			if ($ID == $id) {
				global $INFO;
				if (isset($INFO['meta']['projectfile']))
					$meta = $INFO['meta']['projectfile'];
				else return NULL;
			} else $meta = p_get_metadata($id, 'projectfile', FALSE);
		}
		if (!is_array($meta)) return NULL;
		if (!isset($meta['type'])) return NULL;
		$type = $meta['type'];
		if (!isset(self::$types[$type])) return NULL;
		return new self::$types[$type]($id, $meta);
	}

	protected static function getStringFromMeta($meta, $key, $default = '') {
		if (isset($meta[$key])) {
			$s = $meta[$key];
			if (is_string($s)) return $s;
		}
		if (!is_string($default))
			$default = '';
		return $default;
	}

	protected static function getArrayFromMeta($meta, $key, $default = array(), $sep = FALSE) {
		if (isset($meta[$key])) {
			$a = $meta[$key];
			if (is_string($a) && $sep) {
				$a = explode($sep, $a);				
			}
			if (is_array($a)) return $a;
		}
		if (!is_array($default))
			$default = array();
		return $default;
	}

	protected static function getPosFromMeta($meta, $key, $default = array()) {
		$pos = self::getArrayFromMeta($meta, $key, $default);
		if (isset($pos['pos']) && isset($pos['length']))
			return $pos;
		return array();
	}

	public static function getDependencyFromMeta($meta, $key) {
		$dependency = self::getArrayFromMeta($meta, $key, array(), ';');
		sort($dependency);
		$deps = array();
		foreach($dependency as $dep)
			if ($dep) $deps[] = $dep;
		return $deps;
	}

	public static function getDateFromMeta($meta, $key, $default = FALSE) {
		if (isset($meta[$key])) {
			$v = $meta[$key];
			if (is_numeric($v)) return $v;
		}
		if ($default === FALSE) {
			$default = time();
		}
		return $default;
	}

	public function __construct($id, $meta) {
		$this->id = $id;
		$this->file_path = self::projects_file_path($id, false);
		list($this->file_extension, $this->mimetype) = mimetype($id);
		if (!$this->mimetype || $this->mimetype == 'text/plain' ||
			$this->mimetype == 'application/oct-stream') {
			$finfo = new finfo();
			$this->mimetype = $finfo->file($this->file_path, FILEINFO_MIME_TYPE);
		}
		$this->display = self::getStringFromMeta($meta, 'display');
		$this->highlight = self::getStringFromMeta($meta, 'highlight');
		$this->entertag = self::getPosFromMeta($meta, 'entertag');
		$this->pos = self::getPosFromMeta($meta, 'codepos');
		$this->exittag = self::getPosFromMeta($meta, 'exittag');
		$this->code = self::getStringFromMeta($meta, 'code');
		$this->modified_date = self::getDateFromMeta($meta, 'modified');
		$this->dependency = self::getDependencyFromMeta($meta, 'use');
	}

	public function set_exit_pos($pos) {
		$this->exit_pos = $pos;
	}

	abstract public function type();
	abstract protected function update();

	public function modified_date() { return $this->modified_date; }

	public function update_from($old) {
		if ($old) {
			$update = ($this->type() != $old->type());
			$update |= ($this->code != $old->code());
			$update |= ($this->dependency != $old->dependency());
			if (!$update) {
				$this->modified_date = $old->modified_date();
				return;
			}
		}
		// if the dir does not exist, create
		$dir = dirname($this->file_path);
		if (!file_exists($dir)) mkdir($dir, 0700, TRUE); 
		$this->update();
	}

	public function meta() {
		$meta = array('type' => $this->type());
		if ($this->display) $meta['display'] = $this->display;
		if ($this->highlight) $meta['highlight'] = $this->highlight;
		$meta['codepos'] = $this->pos;
		$meta['exittag'] = $this->exittag;
		$meta['entertag'] = $this->entertag;
		$meta['modified'] = $this->modified_date;
		$meta['code'] = $this->code;
		$meta['use'] = $this->dependency;
		return $meta;
	}

	public function id() { return $this->id; }
	public function mimetype() { return $this->mimetype; }
	public function file_extension() { return $this->file_extension; }
	public function code() { return $this->code; }
	public function pos() { return $this->pos; }
	public function entertag() { return $this->entertag; }
	public function exittag() { return $this->exittag; }
	public function dependency() { return $this->dependency; }
	public function file_path() { return $this->file_path; }
	public function rm() { unlink($this->file_path); }
}

class Projects_file_source extends Projects_file
{
	public function __construct($id, $meta) {
		parent::__construct($id, $meta);
	}

	public function type() { return "source"; }

	public function update() {
		if (file_exists($this->file_path)) {
			$content = file_get_contents($this->file_path);
			if ($content == $this->code) return;
		}
		file_put_contents($this->file_path, $this->code);
		// upload as media
		$data = array();
		$data[0] = $this->file_path;
		$data[1] = mediaFN($this->id);
		$data[2] = $this->id;
		$data[3] = $this->mimetype;
		$data[4] = TRUE;
		$data[5] = 'copy';
		// trigger event
		return trigger_event('MEDIA_UPLOAD_FINISH', $data, '_media_upload_action', true);	}
}

Projects_file::register_file_type("source", Projects_file_source);
