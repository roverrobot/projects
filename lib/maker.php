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
        if (is_a($request, 'Projects_file')) {
            foreach (self::$_handlers as $handler)
                if ($handler->can_handle($request))
                    array_push($handlers, $handler);
        }
        return $handlers;
    }

    protected function run($file, $command, $code=FALSE) {
        $dir = dirname(explode(' ', $command)[0]);
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        $env = NULL;
        if (!in_array($dir, $paths)) {
            $paths[] = $dir;
            $env = array('PATH' => implode(PATH_SEPARATOR, $paths));
        }
        $log = $file->log_file();
        $descs = array(
            array('pipe', 'r'),
            array('file', $log, 'w'),
            array('file', $log, 'a'));
        $proc = proc_open($command, $descs, $pipes, dirname($log), $env);
        if ($code) fwrite($pipes[0], $code);
        $return = proc_close($proc);
        return $return === 0;
    }
    abstract public function name();
    abstract public function can_handle($file);
    abstract public function auto_dependency($file); 
    abstract public function make($file);
}

Projects_Maker::load();

