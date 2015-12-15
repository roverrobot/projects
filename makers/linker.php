<?php

class Projects_Maker_Linker extends Projects_Maker {
	protected $ld = '';

	public function __construct() {
		$this->ld = find_executable('ld');
	}

	/**
	 * The name of the rule, a human readable string, a unique identifier
	 */
	public function name() { return "linker"; }
	
	protected function is_exe($id) {
		if (isset($_SERVER["WINDIR"])) {
			$ext = strtolower(substr($id, -4));
			return $ext == ".exe" || $ext == ".com";
		}
		return TRUE;
	}

	protected function base($id) {
		if (isset($_SERVER["WINDIR"]))
			return strtolower(substr($id, 0, -4));
		if (strtolower(substr($id, -2)) == ".a")
			return substr($id, 0, -2);
		if (strtolower(substr($id, -3)) == ".so")
			return substr($id, 0, -3);
		if (strtolower(substr($id, -6)) == ".dylib")
			return substr($id, 0, -6);
		return $id;
	}

	/**
	 * whether this rule can make a given target
	 */
	public function can_handle($file) {
		if (!$this->ld) return FALSE;
		$id = $file->id();
		if (!$this->is_exe($id)) return FALSE;
		if ($file->code()) return FALSE;
		$deps = $file->dependency();
		if (!$deps) {
			$dep = Projects_file::file($this->base($id) . '.o');
			if (!$dep) return FALSE;
			$deps = array($dep->id());
		} else $deps = array_keys($deps);

		foreach ($deps as $dep) {
		    $tail = strtolower(substr($dep, -2));
		    if ($tail == '.o') continue;
			if ($tail == '.a') continue;
		    $tail = strtolower(substr($dep, -3));
			if ($tail == '.so') continue;
		    $tail = strtolower(substr($dep, -6));
			if ($tail == '.dylib') continue;
			return FALSE;
		}
		return TRUE;
	}

	public function auto_dependency($file) {
		$deps = $file->dependency();
		if (!$deps) {
			$dep = Projects_file::file($this->base($file->id()) . '.o');
			if ($dep) return array($dep->id());
		}
		return array();
	}

    public function make($file) {
    	$id = $file->id();
    	$command = $this->ld . ' -o ' . $file->file_path();
		$command .= (strtolower(substr($id, -2)) == ".a") ? ' -static' : ' -dynamic';
		$os = php_uname("s");
		if ($os == 'Darwin')
			$command .= ' -macosx_version_min 10.8';
		$deps = array_keys($file->dependency());
		$c = FALSE;
		foreach ($deps as $dep) {
		    if (strtolower(substr($dep, -2)) == '.o') {
		    	$depfile = Projects_file::file($dep);
		    	if (!$depfile) return FALSE;
		    	$dep_deps = $depfile->dependency();
		    	if ($dep_deps) foreach ($dep_deps as $depdep => $auto) {
		    		if (strtolower(substr($depdep, -2)) == '.c' ||
		    			strtolower(substr($depdep, -4)) == '.cpp' ||
		    			strtolower(substr($depdep, -4)) == '.cxx' ||
		    			strtolower(substr($depdep, -3)) == '.cc') {
		    			$c = TRUE;
			    		break;
		    		}
		    	}
		    }
		    $command .= ' ' . $dep;
		}
		if ($c)
			$command .= " -lc " . ($os == 'Darwin') ? " -lSystem" : "/lib/crt0.o";
    	return $this->run($file, $command);
    }
}
