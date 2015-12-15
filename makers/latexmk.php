<?php

class Projects_Maker_Latexmk extends Projects_Maker
{
    protected $latexmk = '';
    protected $bash = '';

    public function __construct() {
        $this->latexmk = find_executable("latexmk");
        $this->bash = find_executable("bash");
    }

    public function name() { return "latexmk"; }

    private function main_latex_file($id) {
        return substr($id, 0, strlen($id)-3) . 'tex';
    }

    public function can_handle($file) {
        if (!$this->bash) return FALSE;
        if (!$this->latexmk) return FALSE;
        if (strtolower(substr($file->id(), -4)) != '.pdf') return FALSE;
        if (trim($file->code())) return FALSE;
        $tex = $this->main_latex_file($file->id());
        $texfile = self::dependence($tex);
        if ($texfile) return TRUE;
        return FALSE;
    }

    public function make($file) {
        $tex = $this->main_latex_file($file->id());
        $texfile = self::dependence($tex);
        $path = $texfile->file_path();
        $command = $this->latexmk . " -pdf $path";
        return $this->run($file, $this->bash . ' -l', $command);
    }

    public function auto_dependency($file) {
        $tex = $this->main_latex_file($file->id());
        $texfile = self::dependence($tex);
        if ($texfile) return array($tex);
        return array(); 
    }
}
