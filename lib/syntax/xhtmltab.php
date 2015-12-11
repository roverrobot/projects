<?php

require_once dirname(__FILE__) . '/xhtml.php';
require_once dirname(__FILE__) . '/../project/file.php';

class Projects_XHTMLTabs {
	private $dom = NULL;
	private $root = NULL;
	protected $list = NULL;
	protected $panels = array();

	public function importNode($node) { return $this->dom->importNode($node, TRUE); }
	public function newText($text) {
		return $this->dom->createTextNode($text);
	}

	public function newElement($name, $attributes=array(), $value = NULL) {
		$e = $this->dom->createElement($name, $value);
		foreach ($attributes as $attr => $v)
			$e->setAttribute($attr, $v);
		return $e;
	}

	public function newTab($tab) {
		if (!is_subclass_of($tab, 'Projects_XHTMLTab', FALSE)) return;
		$item = $this->newElement('li');
		$link = $this->newElement('a', array('href' => '#' . $tab->id()), $tab->name());
		$item->appendChild($link);
		$this->list->appendChild($item);

		$this->panels[$tab->name()] = $tab;
		$this->root->appendChild($tab->root());
	}

	public function tab($name) { return $this->panels[$name]; }

	public function xhtml() {
		return $this->dom->saveXML($this->root);
	}

	public function __construct() {
		$this->dom = new DOMDocument();
		$this->root = $this->newElement('div', array('class' => 'PROJECTS_TABS'));
		$this->dom->appendChild($this->root);

		$this->list = $this->newElement('ul');
		$this->root->appendChild($this->list);
	}
}

class Projects_XHTMLTab {
	private $name = '';
	private $parent = NULL;
	public $root = NULL;

	public function root() { return $this->root; }
	public function name() { return $this->name; }
	public function id() { return 'PROJECTS_TAB_' . $this->name; }

	public function importNode($node) { return $this->parent->importNode($node); }
	public function newElement($name, $attributes=array(), $value = NULL) {
		return $this->parent->newElement($name, $attributes, $value);
	}
	protected function newText($text) {
		return $this->parent->newText($text);
	}

	public function loadElement($text) {
		$dom = DOMDocument::loadXML($text);
		return $this->importNode($dom->documentElement);
	}

	public function __construct($parent, $name) {
		$this->parent = $parent;
		$this->name = $name;
		$this->root = $this->newElement('div', array('id' => $this->id()));
	}
}

class Projects_SummaryTab extends Projects_XHTMLTab {
	protected $actions = NULL;
	protected $content = NULL;

	public function newAction($action) {
		if ($this->actions->firstChild != NULL)
			$this->actions->appendChild($this->newElement('span', array(), ", "));
		$span = $this->loadElement($action);
		$this->actions->appendChild($span);
	}

	public function setContent($content) {
		$this->content->appendChild($this->loadElement($content));
	}

	public function __construct($parent, $file) {
		global $ID;
		global $REV;

		parent::__construct($parent, 'Summary');
		$list = $this->newElement('ul');
		$this->root->appendChild($list);

		$info = $this->newElement('li');
		$text =  ': ' . $file->type() . ' file';
		$info->appendChild($this->loadElement(html_wikilink($ID)));
		$info->appendChild($this->newElement('span', array(), $text));
		$list->appendChild($info);

        $format = 'D M d, Y \a\t g:i:s a';
        if (date_default_timezone_get() == 'UTC') $format .= ' e';
        $date = ($REV) ? $REV : $file->modified_date();
        if (!$date) {
        	$meta = Projects_file::file($file->id());
        	$date = ($meta)? $meta->modified_date() : time();
        }
        $updated = $this->newElement('li', array(), 'modified on: ' . date($format, $date));
		$list->appendChild($updated);

		$actions = $this->newElement('li', array(), "Actions: ");
		$list->appendChild($actions);
		$this->actions = $this->newElement('span');
		$actions->appendChild($this->actions);
		$this->newAction(manage_files_button($ID));
		if (!$REV) $this->newAction(download_button($ID));
		if (auth_quickaclcheck($ID) >= AUTH_DELETE)
            $this->newAction(delete_button($ID));

		$this->content = $this->newElement('div', array(
			'id' => 'PROJECTS_content'));
		$this->root->appendChild($this->content);
	}	
}

class Projects_DependencyTab extends Projects_XHTMLTab {
	protected $list = NULL;
	protected $panels = NULL;
	protected $editable = FALSE;

	public function addDependence($dependence, $automatic) {
		$li = $this->newElement('li');
		$this->list->appendChild($li);
		$span = $this->newElement('span', array('use' => $dependence, 'class' => 'dependency'));
		$li->appendChild($span);
		$use = $this->loadElement(html_wikilink($dependence));
		$span->appendChild($use);
		if ($automatic)
			$li->appendChild($this->newText('(automatic)'));
		else if ($this->editable) {
			$li->appendChild($this->newText('('));
			$input = $this->newElement('a', array('class' => 'remove_dependency action', 'use'=>$dependence, 'href' => ''), 'remove');
			$li->appendChild($input);
			$li->appendChild($this->newText(')'));
		}
	}

	public function __construct($parent, $deps) {
		global $REV;
		parent::__construct($parent, 'Dependency');
		$this->list = $this->newElement('ul', array('class' => 'dependency_list'));
		$this->root->appendChild($this->list);

		$this->editable = (!$REV && auth_quickaclcheck($ID) >= AUTH_EDIT);
		if ($this->editable) {
			$li = $this->newElement('li');
			$this->list->appendChild($li);
			$span = $this->newElement('span', array('class' => 'dependency'));
			$li->appendChild($span);
			$input = $this->newElement('input', array('id' => 'new_dependency_name'));
			$span->appendChild($input);
			$input = $this->newElement('a', array('id' => 'add_dependency', 'href' => '', 'class' => 'action'), 'add');
			$li->appendChild($input);
		}
		if ($deps) foreach ($deps as $dep => $auto) $this->addDependence($dep, $auto);
		if ($this->editable) {
			$controls = $this->newElement('div', array('id' => 'dependency_update_controls'));
			$this->root->appendChild($controls);
			$form = new Doku_Form(array('id' => 'dependency_update_form'));
	        $form->addHidden('new', '');
	        $form->addHidden('old', '');
	        $form->addElement(form_makeButton('submit', 'update_dependency', 'update', array('id' => 'update_dependency')));
			$controls->appendChild($this->loadElement($form->getForm()));
			$controls->appendChild($this->loadElement(cancel_button()));
		}
	}	
}

class Projects_RecipeTab extends Projects_XHTMLTab {
 	public function __construct($parent, $file, $read_only) {
		parent::__construct($parent, 'Recipe');
    	$editor = Projects_editor::editor('', $file->code(), $file->highlight());
    	$editor->read_only = $read_only;
    	$maker = $this->newElement('div', array(), 'Maker: ');
    	$this->root->appendChild($maker);
    	if (auth_quickaclcheck($file->id()) >= DOKU_EDIT) {
    		$makers = Projects_maker::maker($file);
    		if (count($makers) > 1) {
	    		$select = $this->newElement('select', array('id' => 'PROJECTS_maker', 'name' => 'maker'));
	    		$maker->appendChild($select);
	    		foreach ($makers as $m) {
	    			$prop = array('value' => $m->name());
	    			if ($m->name() == $file->maker()) $prop['selected'] = 1;
	    			$opt = $this->newElement('option', $prop, $m->name());
	    			$select->appendChild($opt);
	    		}
	    		$controls = $this->newElement('span', array('id' => 'maker_controls', 'maker' => $file->maker()));
	    		$maker->appendChild($controls);
				$form = new Doku_Form(array('id' => 'maker_select_form'));
	        	$form->addElement(form_makeButton('submit', 'set_maker', 'update', array('id' => 'set_maker')));
				$controls->appendChild($this->loadElement($form->getForm()));
				$controls->appendChild($this->loadElement(cancel_button()));
	    	} else {
	    		$m = ($makers) ? $makers[0]->name() : 'none';
	    		$maker->appendChild($this->newText($m));
	    	}
    	} else $maker->appendChild($this->newText('Maker: ' . $file->maker()));
    	$content = $editor->xhtml('recipe', 'savecontent');
    	$this->root->appendChild($this->loadElement("<div>$content</div>"));
	}
}

class Projects_LogTab extends Projects_XHTMLTab {
 	public function __construct($parent, $file) {
		parent::__construct($parent, 'Log');
		$log = $file->log();
		if (!$log) return;
    	$editor = Projects_editor::editor($file->log_file(), $file->log(), '');
    	$editor->read_only = TRUE;
    	$content = $editor->xhtml('log', 'show');
    	$this->root->appendChild($this->loadElement($content));
    }
}
