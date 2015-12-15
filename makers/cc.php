<?php

class Projects_Maker_CC extends Projects_Maker {
    protected $compilers = array();
    protected $exts = array('.c' => 'cc', '.cpp' => 'cxx', '.cxx' => 'cxx', '.cc' => 'cxx');

    public function __construct() {
        $this->compilers['cc'] = find_executable('cc');
        $this->compilers['cxx'] = find_executable('c++');
    }
	/**
	 * The name of the rule, a human readable string, a unique identifier
	 */
	public function name() { return 'cc'; }

    /*
	 * whether this rule can make a given target
	 */
	public function can_handle($file) {
        if ($file->code()) return FALSE;
        $name = $file->id();
		if (strtolower(substr($name, -2)) != '.o') return false;

        $base = substr($name, 0, -2);
        foreach ($this->exts as $ext => $compiler) {
            $dep = self::dependence($base . $ext);
            if ($dep) break;
        }
        if (!$dep) return FALSE;
        return ($compiler && $this->compilers[$compiler]);
    }

    public function make($file) {
        $base = substr($file->id(), 0, -2);

        foreach ($this->exts as $ext => $compiler) {
            $source = self::dependence($base . $ext);
            if ($source) break;
        }
        if (!$source) return FALSE;
        if ($compiler && !$this->compilers[$compiler]) return FALSE;

        $command = $this->compilers[$compiler] . ' -O3 -c ' . $source->file_path();
        return $this->run($file, $command);
    }

    public function auto_dependency($file) {
        $base = substr($file->id(), 0, -2);
        foreach ($this->exts as $ext => $compiler) {
            $dep = self::dependence($base . $ext);
            if ($dep) return array($dep->id());
        }
        return array();
    }
}
