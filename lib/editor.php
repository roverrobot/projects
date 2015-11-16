<?php

define(PROJECTS_EDITORS_ROOT, dirname(__FILE__) . '/../editor');

require_once dirname(__FILE__) . '/load.php';

abstract class Projects_editor {
	private static $editors = array();
	abstract static public function name();
	abstract public function xhtml($editor_id);

    abstract protected function get_highlight($file, $code);

    public $read_only = TRUE;
    protected $code;
    protected $highlight;

    public function __construct($file, $code, $default_highlight) {
        $this->code = $code;
        $this->highlight = $this->get_highlight($file, $code);
        if (!$this->highlight) $this->highlight = $default_highlight;
    }

	public static function load_editors() {
        // take a snapshot of currently defined classes
        $old_classes = get_declared_classes();
        // load the dirs
        load_dir(PROJECTS_EDITORS_ROOT);
        // get an array of newly defined classes from the includes
        $classes = get_declared_classes();
        $new_classes = array_diff($classes, $old_classes);
        // inspect each new class
        foreach ($new_classes as $class) {
            if (is_subclass_of($class, 'Projects_editor')) {
        		$ref_class = new ReflectionClass($class);
        		if (!$ref_class->isAbstract()) {
                    self::$editors[$class::name()] = $class;
                }
            }
        }
    }

    public static function editors() { return array_keys(self::$editors); }

    public static function default_editor() { return array_keys(self::$editors)[0]; }

    public static function editor($file, $code, $default_highlight) { 
        $name = self::default_editor();
    	if (!isset(self::$editors[$name])) return NULL;
    	$class = self::$editors[$name];
        return new $class($file, $code, $default_highlight);
    }
}

Projects_editor::load_editors();