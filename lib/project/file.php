<?php

define(PROJECTS_ROOT, DOKU_INC . '/data/projects/');

require_once dirname(__FILE__) . '/../analyzer.php';
require_once dirname(__FILE__) . '/../maker.php';

define(PROJECTS_MADE, 0);
define(PROJECTS_MODIFIED, 1);

class Projects_make_progress {
	private $made = array();
	private $making = '';
	private $queue = array();
	private $pid = 0;
	private $started = 0;
	private $history = array();

	public function made() { return $this->made; }
	public function making() { return $this->making; }
	public function queue() { return $this->queue; }
	public function pid() { return $this->pid; }
	public function started() { return $this->started; }
	public function history() { return $this->history; }

	public function __construct($file, $history) {
		$this->pid = getmypid();
		if ($file->dependency())
			$this->queue = array_keys($file->dependency());
		else $this->queue = array();
		$this->queue[] = $file->id();
		$this->started = time();
	}

	public function progress() {
		if ($this->making) $this->made[] = $this->making;
		$this->making = array_shift($this->queue);
		return $this->making;
	}
}

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
	protected $modified = FALSE;
	protected $status = PROJECTS_MADE;

	public static function register_file_type($type, $class) {
		self::$types[$type] = $class;
	}

	public static function projects_file_path($id, $check_existence=true) {
	    $path = PROJECTS_ROOT . implode('/', explode(':', $id));
	    if ($check_existence && !file_exists($path)) return false;
	    return $path;
	}

	public static function file($id, $meta=NULL) {
		if ($meta == NULL)
			$meta = unserialize(io_readFile(metaFN($id, '.projects'), FALSE));
		if (!is_array($meta)) return NULL;
		if (!isset($meta['type'])) return NULL;
		$type = $meta['type'];
		if (!isset(self::$types[$type])) return NULL;
		return new self::$types[$type]($id, $meta);
	}

	public static function remove($id) {
		$file = self::file($id);
		if ($file) $file->rm();
		$meta = metaFN($id, '.projects');
		if (file_exists($meta)) unlink($meta);
	}

	public static function project_files($ns) {
	    $dir_path = DOKU_INC . 'data/pages/' . implode('/', explode(':', $ns));
	    $dh = @dir($dir_path);
	    if (!$dh) return array(array(), array());
	    $files = array();
	    $dirs = array();

	    while (false !== ($entry = $dh->read())) {
	        if ($entry[0] == '.') continue;

	        if (is_dir($dir_path . '/' . $entry)) {
	            $id = ($ns) ? "$ns:$entry" : $entry;
	            array_push($dirs, $id);
	            continue;
	        }

	        if (substr($entry, -4) != '.txt') continue;

	        $entry = substr($entry, 0, strlen($entry)-4);
	        if (!$ns)
	            $id = $entry;
	        else $id = $ns . ':' . $entry;

	        $file = self::file($id);
	        if ($file) $files[$id] = $file;
	    }

	    $dh->close();
	    return array($files, $dirs);
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

	protected static function getArrayFromMeta($meta, $key, $default = array()) {
		if (isset($meta[$key])) {
			$a = $meta[$key];
			if (is_array($a)) return $a;
		}
		return $default;
	}

	protected static function getPosFromMeta($meta, $key, $default = array()) {
		$pos = self::getArrayFromMeta($meta, $key, $default);
		if (isset($pos['pos']) && isset($pos['length']))
			return $pos;
		return array();
	}

	public static function getDependencyFromMeta($meta, $key) {
		$dependency = self::getArrayFromMeta($meta, $key, FALSE);
		if (!$dependency) {
			$a = $meta[$key];
			if (is_string($a)) {
				$a = explode(';', $a);
				$dependency = array();
				foreach ($a as $dep) {
					$dep = trim($dep);
					if ($dep) $dependency[$dep] = FALSE;
				}
			}
		}
		if ($dependency) ksort($dependency);
		return $dependency;
	}

	public static function getDateFromMeta($meta, $key, $default = FALSE) {
		if (isset($meta[$key])) {
			$v = $meta[$key];
			if (is_numeric($v)) return $v;
		}
		return $default;
	}

	public function __construct($id, $meta) {
		$this->id = $id;
		$this->file_path = self::projects_file_path($id, false);
		list($this->file_extension, $this->mimetype) = mimetype($id);
		if (!$this->file_extension)
			$this->file_extension = pathinfo($this->file_path, PATHINFO_EXTENSION);
		if ((!$this->mimetype || $this->mimetype == 'text/plain' ||
			$this->mimetype == 'application/oct-stream') && file_exists($this->file_path))
		{
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
		if (isset($meta['status']))
			$this->status = $meta['status'];
	}

	abstract public function type();
	abstract public function content();

	public function modified_date() { return $this->modified_date; }

	protected function dependency_changed($old) {
		return $this->dependency != $old->dependency();
	}

	protected function is_modified($old) {
		if (!$old) return TRUE;
		$modified = ($this->type() != $old->type());
		$modified = $modified || ($this->code != $old->code());
		$modified = $modified || $this->dependency_changed($old);
		return $modified;
	}

	protected function copy_from($old) {
		if ($old) {
			$this->modified_date = $old->modified_date();
			$this->status = $old->status();
		}
	}

	public function update_from($old) {
		$this->modified = $this->is_modified($old);
		if (!$this->modified) {
			$this->copy_from($old);
			return;
		}
		$this->update();
	}

	protected function update() {
		$this->modified_date = time();
		// if the dir does not exist, create
		$dir = dirname($this->file_path);
		if (!file_exists($dir)) mkdir($dir, 0700, TRUE); 
		$this->save();		
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
		$meta['status'] = $this->status;
		return $meta;
	}

	public function id() { return $this->id; }
	public function highlight() { return $this->highlight; }
	public function mimetype() { return $this->mimetype; }
	public function file_extension() { return $this->file_extension; }
	public function code() { return $this->code; }
	public function pos() { return $this->pos; }
	public function entertag() { return $this->entertag; }
	public function exittag() { return $this->exittag; }
	public function dependency() { return $this->dependency; }
	public function file_path() { return $this->file_path; }
	public function status() { return $this->status; }

	public function is_making() {
		return is_a($this->status, 'Projects_make_progress');
	}

	public function killed() {
		if (!$this->is_making()) return;
		$making = $this->status()->making();
		$history = $this->status()->history();
		$this->add_error('file generation canceled');
		$this->modified = TRUE;
		$this->save();
		foreach ($history as $hist) {
			$file = self::file($hist);
			if ($file->is_making() && $file->status()->pid() == $this->pid())
				$file->killed();
		}
		$file = self::file($making);
		$file->killed();
	}

	protected function wait() {
		$file = NULL;
		while (TRUE) {
			$file = self::file($this->id);
			if (!$file) {
				$this->add_error('Wiki page deleted?');
				return $this->status;
			} 
			if (!$file->is_making())
				break;
			sleep(1);
		}
		if ($file->status != PROJECTS_MADE) return $file->status;
		return $file->modified_date();
	}

	protected function add_error($error) {
		if (!is_array($this->status))
			$this->status = array($this->id => array($error));
		else if (!isset($this->status[$this->id]))
			$this->status[$this->id] = array($error);
		else $this->status[$this->id][] = $error;
	}

	public function rm() {
		if (file_exists($this->file_path))
			unlink($this->file_path);
		$media = mediaFN($this->id);
		if (file_exists($media)) unlink($media);
	}

	protected function progress() {
		if ($this->is_making() && $this->entertag) {
			$this->status()->progress();
			$this->modified = TRUE;
			$this->save();
		}
	}

	public function make($history, $force) {
		// if it is currently being made, wait until it is done
		if ($this->is_making())
			return $this->wait();
		// make dependency
		if (in_array($this->id, $history)) {
			$loop = 'dependency loop:' . html_wikilink($this->id);
			foreach($history as $dep) $loop . ' ' . html_wikilink($dep);
			$this->add_error($loop);
			return $this->status;
		}
		$date = $this->modified_date;
		$this->status = new Projects_make_progress($this, $history);
		$history[] = $this->id;
		if ($this->dependency) foreach ($this->dependency as $dep => $auto) {
			$this->progress();
			$file = Projects_Maker::dependence($dep);
			if (!$file) {
				$this->add_error('do not know how to make the dependence ' . html_wikilink($dep));
				return $this->status;
			}
//			echo "<pre>"; var_dump($file); echo "</pre>";
			$result = $file->make($history, !file_exists($file->file_path()));
			if (is_array($result)) {
				$this->status = $result;
				$this->add_error('failed to generate ' . html_wikilink($dep));
				return $this->status;
			}
			if ($result > $date) $date = $result;
		}
		$this->progress();
		// make this file
		return $date;
	}

	public function save() {
		if ($this->modified) {
			$meta = metaFN($this->id, '.projects');
			io_saveFile($meta, serialize($this->meta()));
			$this->modified = FALSE;
		}
	}

	abstract public function analyze();
}

class Projects_file_source extends Projects_file
{
	public function __construct($id, $meta) {
		parent::__construct($id, $meta);
	}

	public function type() { return "source"; }

	public function update() {
		parent::update();
		// save to file
		if (file_exists($this->file_path)) {
			$content = file_get_contents($this->file_path);
			if ($content == $this->code) return;
		}
		file_put_contents($this->file_path, $this->code);
		// upload as media
		io_createNameSpace($this->id, 'media');
		copy($this->file_path, mediaFN($this->id));
	}

	public function content() {
		return $this->code;
	}

    public function analyze() {
        $deps = Projects_Analyzer::auto_dependency($this);
        foreach ($deps as $dep)
			$this->dependency[$dep] = TRUE;
    }

	public function make($history, $force) {
		$result = parent::make($history, $force);
		if (is_a($this->status, 'Projects_make_progress'))
			$this->status = PROJECTS_MADE;
		else if (is_array($result))
			$this->status = $result;
		else if ($result < $this->modified_date) return $this->modified_date;
		$this->modified = TRUE;
		$this->save();
		return $result;
	}

}

class Projects_file_generated extends Projects_file
{
	protected $maker = '';

	public function maker() { return $this->maker; }

	public function __construct($id, $meta = array('type' =>'generated')) {
		parent::__construct($id, $meta);
		if (isset($meta['maker']))
			$this->maker = $meta['maker'];
	}

	protected function is_modified($old) {
		if (parent::is_modified($old)) return TRUE;
		return (!$old || $this->maker != $old->maker());
	}

	protected function copy_from($old) {
		parent::copy_from($old);
		if ($old) $this->maker = $old->maker();
	}

	public function meta() {
		$meta = parent::meta();
		if ($this->maker)
			$meta['maker'] = $this->maker;
		return $meta;
	}

	public function type() { return "generated"; }

	public function update() {
		$this->status = PROJECTS_MODIFIED;
		parent::update();
		// save to file
		if (file_exists($this->file_path)) $this->rm();
	}

	protected function dependency_changed($old) {
		$deps = array();
		if ($this->dependency) {
			foreach ($this->dependency as $dep => $auto)
				if (!$auto) $deps[] = $dep;
		}
		$old_deps = array();
		if ($old->dependency()) {
			foreach ($old->dependency() as $dep => $auto)
				if (!$auto) $old_deps[] = $dep;
		}
		if ($deps != $old_deps) return TRUE;

		$old_deps = $old->dependency();
		$old_deps = ($old_deps)? array_keys($old_deps) : array();
		if ($this->dependency) {
			foreach ($this->dependency as $dep => $auto)
				if ($auto && !in_array($dep, $old_deps)) return TRUE;
		}
		return FALSE;
	}

	public function content() {
		if (file_exists($this->file_path))
			return file_get_contents($this->file_path);
		return '';
	}

	public function log_file() {
        return $this->file_path . '.make.log';
    }

	public function log() {
        $log = $this->log_file();
        if (file_exists($log)) return file_get_contents($log);
        return '';
	}

	public function rm() {
		parent::rm();
        $log = $this->file_path . '.make.log';
        if (file_exists($log)) unlink($log);
	}

	public function make($history, $force) {
		// make the dependencies
		if (is_array($this->status)) $force = TRUE;
		$result = parent::make($history, $force);
		if (is_array($result)) {
			$this->status = $result;
			$this->modified = TRUE;
			$this->save();
			return $result;
		}

		// now the status has to be PROJECTS_MODIFIED, i.e., it needs to be made.
		if (!$force && $date == $this->modified_date) {
			$this->status = PROJECTS_MADE;
			$this->modified = TRUE;
			$this->save();
			return $this->modified_date;
		}
		$this->rm();
		$this->save();
		if ($this->maker)
			$maker = Projects_Maker::maker($this->maker);
		else $maker = NULL;
		if (!$maker)
			$this->add_error('no available maker');
		else if (!$maker->make($this))
			$this->add_error('make failed');
		else {
			$this->modified_date = time();
			$this->status = PROJECTS_MADE;
			// analyze the content for autodependency
	        $deps = Projects_Analyzer::auto_dependency($this);
	        foreach ($deps as $dep)
				$this->dependency[$dep] = TRUE;
		}
		$this->modified = TRUE;
		$this->save();
		return $this->modified_date;
    }

    public function analyze() {
		if (!$this->maker) {
			$makers = Projects_Maker::maker($this);
			if ($makers) $maker = $makers[0];
		} else $maker = Projects_Maker::maker($this->maker);
		if ($maker) {
			$this->maker = $maker->name();
			$deps = $maker->auto_dependency($this);
			foreach ($deps as $dep)
				$this->dependency[$dep] = TRUE;
		} else $this->maker = '';
    }

}

Projects_file::register_file_type("source", Projects_file_source);
Projects_file::register_file_type("generated", Projects_file_generated);
