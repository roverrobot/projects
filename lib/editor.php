<?php

define(EDITORS_ROOT, dirname(__FILE__) . '/../editor');

class Projects_Editor_Manager extends Doku_Component_Manager {
    private static $manager = NULL;
    private $editors = array();

    static public function manager() {
        if (!self::$manager)
            self::$manager = new Projects_Editor_Manager;
        return self::$manager;
    }

    protected function handle($class) {
        if (is_subclass_of($class, 'Projects_editor')) {
            $this->editors[] = $class;
        }
    }

    public function editors() { return $this->editors; }

    public function default_editor() {
        return ($this->editors) ? $this->editors[0] : NULL;
    }

    public function editor($file, $code, $default_highlight) { 
        $name = $this->default_editor();
        if (!$name) return NULL;
        return new $name($file, $code, $default_highlight);
    }

    public function __construct() {
        $this->load(EDITORS_ROOT);
    }
}

abstract class Projects_editor {
	abstract static public function name();
	abstract protected function editor_xhtml($editor_id, $do);

    abstract protected function get_highlight($file, $code);

    public function xhtml($editor_id, $do) {
        $controls = '<div>';
        if (!$this->read_only) {
            $form = new Doku_Form(array('class' => 'editor_edit_form', 'editor' => $editor_id));
            $form->addElement(form_makeButton('submit', '', 'edit'));
            $controls .= $form->getForm();
            $controls .= '<div class="editor_save_controls">';
            $form = new Doku_Form(array('class' => 'editor_save_form', 'editor' => $editor_id));
            $form->addElement(form_makeButton('submit', 'savecontent', 'save'));
            $controls .= $form->getForm() . cancel_button() . '</div>';
        }
        return $controls . $this->editor_xhtml($editor_id, $do) . '</div>';

    }
    public $read_only = TRUE;
    protected $code;
    protected $highlight;

    public function __construct($file, $code, $default_highlight) {
        $this->code = $code;
        $this->highlight = $this->get_highlight($file, $code);
        if (!$this->highlight) $this->highlight = $default_highlight;
    }
}
