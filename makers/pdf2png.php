<?php

class Projects_Maker_Pdf2png extends Projects_Maker
{
    protected $convert = '';

    public function __construct() {
        $this->convert = find_executable("convert");
    }

    public function name() { return "pdf2png"; }

    private function pdf_file($id) {
        return substr($id, 0, strlen($id)-3) . 'pdf';
    }

    public function can_handle($file) {
        if (!$this->convert) return FALSE;
        $id = $file->id();
        if (strtolower(substr($id, -4)) != '.png') return FALSE;
        $pdf = $this->pdf_file($id);
        $file = self::dependence($pdf);
        if ($file) return TRUE;
        return FALSE;
    }

    public function make($file) {
        $pdf = $this->pdf_file($file->id());
        $pdffile = self::dependence($pdf);
        if (!$pdffile) return FALSE;
        $pdfpath = $pdffile->file_path();
        $filepath = $file->file_path();

        $command = $this->convert . " -density 150x150 $pdfpath $filepath";
        return $this->run($file, $command);
    }

    public function auto_dependency($file) {
        $pdf = $this->pdf_file($file->id());
        $pdffile = Projects_file::file($pdf);
        if (!$pdffile) return array();
        return array($pdffile->id());
    }
}
