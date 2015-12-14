<?php

class Projects_Maker_Latexmk extends Projects_Maker
{
    protected $latexmk = '';

    public function __construct() {
        $this->latexmk = find_executable("latexmk");
    }

    public function name() { return "latexmk"; }

    private function main_latex_file($id) {
        return substr($id, 0, strlen($id)-3) . 'tex';
    }

    public function can_handle($file) {
        if (!$this->latexmk) return FALSE;
        if (strtolower(substr($id, -4)) != '.pdf') return FALSE;
        if (trim($file->code())) return FALSE;
        $tex = $this->main_latex_file($id);
        $texfile = Projects_file::file($tex);
        if ($texfile) return TRUE;
        return FALSE;
    }

    public function make($file) {
        $path = $texfile->file_path();
        $command = $this->latexmk . " -pdf $path";
        return $this->run($file, $command);
    }
}
