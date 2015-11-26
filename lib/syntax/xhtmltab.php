<?php

require_once dirname(__FILE__) . '/xhtml.php';

class Projects_XHTMLTabs {
	private $dom = NULL;
	private $root = NULL;
	protected $list = NULL;
	protected $panels = array();

	public function importNode($node) { return $this->dom->importNode($node, TRUE); }
	public function newElement($name, $attributes=array(), $value = NULL) {
		$e = $this->dom->createElement($name, $value);
		foreach ($attributes as $attr => $value)
			$e->setAttribute($attr, $value);
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
		$this->root = $this->newElement('div', array(
			'id' => 'PROJECTS_TABS',
			'name' => 'PROJECTS_TABS'));

		$this->list = $this->newElement('ul');
		$this->root->appendChild($this->list);
	}
}

class Projects_XHTMLTab {
	private $name = '';
	private $parent = NULL;
	protected $root = NULL;

	public function root() { return $this->root; }
	public function name() { return $this->name; }
	public function id() { return 'PROJECTS_TAB_' . $this->name; }

	public function importNode($node) { return $this->parent->importNode($node); }
	protected function newElement($name, $attributes=array(), $value = NULL) {
		return $this->parent->newElement($name, $attributes, $value);
	}

	public function __construct($parent, $name) {
		$this->parent = $parent;
		$this->name = $name;
		$this->root = $this->newElement('div', array('id' => $this->id()));
	}
}

class Projects_SummaryTab extends Projects_XHTMLTab {
	protected $actions = NULL;
	protected $info = NULL;
	protected $content = NULL;

	public function setUpdate($date) {
        $format = 'D M d, Y \a\t g:i:s a';
        if (date_default_timezone_get() == 'UTC') $format .= ' e';
        $updated = date($format, $date);
		$this->info->appendChild($this->newElement('span', array(), $updated));
	}

	protected function loadElement($text) {
		$dom = DOMDocument::loadXML($text);
		return $this->importNode($dom->documentElement);
	}

	public function newAction($action) {
		if ($this->actions->firstChild != NULL)
			$this->actions->appendChild($this->newElement('span', array(), ", "));
		$span = $this->loadElement($action);
		$this->actions->appendChild($span);
	}

	public function setContent($content) {
		$this->content->appendChild($this->loadElement($content));
	}

	public function __construct($parent, $attr) {
		global $ID;
		global $REV;

		parent::__construct($parent, 'Summary');
		$list = $this->newElement('ul');
		$this->root->appendChild($list);

		$this->info = $this->newElement('li', array());
		$text =  ': ' . $attr['type'] . ' last updated on ';
		$this->info->appendChild($this->loadElement(html_wikilink($ID)));
		$this->info->appendChild($this->newElement('span', array(), $text));
		$list->appendChild($this->info);

		$actions = $this->newElement('li', array(), "Actions: ");
		$list->appendChild($actions);
		$this->actions = $this->newElement('span');
		$actions->appendChild($this->actions);
		$this->newAction(manage_files_button($ID));
		if (!$REV) $this->newAction(download_button($ID));
		if (auth_quickaclcheck($ID) >= AUTH_DELETE)
            $this->newAction(delete_button($ID));

		$this->content = $this->newElement('div', array(
			'class' => 'PROJECTS_content', 
			'id' => 'PROJECTS_content'));
		$this->root->appendChild($this->content);
	}	
}