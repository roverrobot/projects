<?php

define(MAKER_ROOT, dirname(__FILE__) . '/../makers/');

require_once dirname(__FILE__) . '/load.php';
require_once dirname(__FILE__) . '/analyzer.php';

abstract class Projects_Maker {
    static private $_handlers = array();

    static public function load() {
        $old_classes = get_declared_classes();
        // load the dirs
        load_dir(MAKER_ROOT);

        // get an array of newly defined classes from the includes
        $classes = get_declared_classes();
        $new_classes = array_diff($classes, $old_classes);

        foreach ($new_classes as $class)
            if (is_subclass_of($class, 'Projects_Maker') &&
                !is_abstract_class($class)) {
                $handler = new $class;
                self::$_handlers[$handler->name()] = $handler;
            }
    }

    static public function maker($request) {
        if (is_string($request)) {
            if (!isset(self::$_handlers[$request])) return FALSE;
            return self::$_handlers[$request];
        }
        $handlers = array();
        foreach (self::$_handlers as $handler)
            if ($handler->can_handle($file))
                array_push($handlers, $handler);
        return $handlers;
    }


    abstract public function name();
    abstract public function can_handle($id, $meta);
    abstract public function make($file);
}

Projects_Maker::load();

