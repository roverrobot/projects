<?php

require_once dirname(__FILE__) . '/project/file.php';

define(FORMATTER_ROOT, dirname(__FILE__) . '/../formatter/');
/** a formatter for the contents of generated files */

class Projects_Formatter_Manager extends Doku_Component_Manager {
    private $formatters = array();
    static private $manager = NULL;

    static public function manager() {
        if (!self::$manager)
            self::$manager = new Projects_Formatter_Manager;
        return self::$manager;
    }

    protected function handle($class) {
        if (is_subclass_of($class, 'Projects_formatter')) {
            $handler = new $class;
            $this->formatters[$handler->name()] = $handler;
        }
    }

    public function xhtml($file) {
        if (is_subclass_of($file, 'Projects_file') && $file) {
            foreach ($this->formatters as $handler) {
                $mime = $file->mimetype();
                if (!$mime && file_exists($file->file_path())) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file->file_path());
                }
                if ($handler->can_handle($file->mimetype())) {
                    $doc = $handler->format($file);
                    if ($doc) return $doc;
                }
            }
        }
        return '<p/>';
    }
}

abstract class Projects_formatter {
    abstract public function can_handle($mimetype);
    abstract public function format($file);
}
