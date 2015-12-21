<?php

define(ANALYZER_ROOT, dirname(__FILE__).'/../analyzers/');

class Projects_Analyzer_Manager extends Doku_Component_Manager {
    private $analyzers = array();
    private static $manager = NULL;

    public static function manager() {
        if (!self::$manager)
            self::$manager = new Projects_Analyzer_Manager;
        return self::$manager;
    }

    protected function handle($class) {
        if (is_subclass_of($class, 'Projects_Analyzer')) {
            $handler = new $class;
            $this->analyzers[$handler->name()] = $handler;
        }
    }

    public function auto_dependency($file) {
        $deps = array();
        if (is_subclass_of($file, 'Projects_file') && $file) {
            foreach ($this->analyzers as $handler) {
                if ($handler->can_handle($file)) {
                    $new = $handler->analyze($file);
                    if ($new && is_array($new)) $deps = array_merge($deps, $new);
                }
            }
        }
        return $deps;
    }

    public function __construct() {
        $this->load(ANALYZER_ROOT);
    }
}

abstract class Projects_Analyzer {
    abstract public function name();
    abstract public function can_handle($file);
    abstract public function analyze($file);

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
