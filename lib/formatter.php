<?php

require_once dirname(__FILE__) . '/project/file.php';

define(PROJECTS_FORMATTER_ROOT, dirname(__FILE__) . '/../formatter/');
/** a formatter for the contents of generated files */
abstract class Projects_formatter {
    abstract public function can_handle($mimetype);
    abstract public function format($file);

    static private $_handlers = array();

    static public function load() {
        $old_classes = get_declared_classes();
        // load the dirs
        load_dir(PROJECTS_FORMATTER_ROOT);

        // get an array of newly defined classes from the includes
        $classes = get_declared_classes();
        $new_classes = array_diff($classes, $old_classes);

        foreach ($new_classes as $class)
            if (is_subclass_of($class, 'Projects_formatter')) {
                $handler = new $class;
                self::$_handlers[] = $handler;
            }
    }

    static public function xhtml($file) {
        if (is_subclass_of($file, 'Projects_file') && $file) {
            foreach (self::$_handlers as $handler) {
                if ($handler->can_handle($file->mimetype())) {
                    $doc = $handler->format($file);
                    if ($doc) return $doc;
                }
            }
        }
        return '<p/>';
    }
}

Projects_formatter::load();
