<?php

define(ANALYZER_ROOT, dirname(__FILE__).'/../analyzers/');

require_once dirname(__FILE__) . '/load.php';

abstract class Projects_Analyzer {
    abstract public function name();
    abstract public function can_handle($file);
    abstract public function analyze($file);

    static private $_handlers = array();

    static public function load() {
        $old_classes = get_declared_classes();
        // load the dirs
        load_dir(ANALYZER_ROOT);

        // get an array of newly defined classes from the includes
        $classes = get_declared_classes();
        $new_classes = array_diff($classes, $old_classes);

        foreach ($new_classes as $class)
            if (is_subclass_of($class, 'Projects_Analyzer')) {
                $handler = new $class;
                self::$_handlers[$handler->name()] = $handler;
            }
    }

    static public function auto_dependency($file) {
        $deps = array();
        if (is_subclass_of($file, 'Projects_file') && $file) {
            foreach (self::$_handlers as $handler) {
                if ($handler->can_handle($file)) {
                    $new = $handler->analyze($file);
                    if ($new && is_array($new)) $deps = array_merge($deps, $new);
                }
            }
        }
        return $deps;
    }

    protected static function absoluteID($ns, $name) {
        if ($name[0] == '.') {
            if ($name[1] == '.') {
                $pos = ($name[2] == ':')? 3 : 2;
                return absoluteID(getNS($ns), substr($name, $pos));
            }

            $pos = ($name[1] == ':')? 2 : 1;
            return absoluteID($ns, substr($name, $pos));
        }
        
        if ($name[0] != ':' && $ns)
            return $ns . ':' . $name;

        return $name;
    }

}

Projects_Analyzer::load();
