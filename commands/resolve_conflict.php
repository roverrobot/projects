<?php

require_once(dirname(__FILE__) . '/../lib/project/file.php');
require_once(dirname(__FILE__) . '/../lib/syntax/xhtml.php');

function splitOp($op, $against) {
    if ($against->type == 'add') return array(FALSE, $op);
    $n = count($against->orig);
    if ($op->type == 'add' || $n >= count($op->orig))
        return array($op, FALSE);
    $head = array_slice($op->orig, 0, $n);
    $tail = array_slice($op->orig, $n);
    switch ($op->type) {
        case 'change':
            if ($gainst->type == 'delete') {
                $op->orig = $tail;
                return array(new _DiffOp_Delete($head), $op);
            }
            $op->orig = $head;
            return array($op, new _DiffOp_Delete($tail));
        case 'copy':
            $op->orig = $head;
            $op->closing = $head;
            return array($op, new _DiffOp_Copy($tail));
        case 'delete':
            $op->orig = $head;
            return array($op, new _DiffOp_Delete($tail));
    }
    return FALSE;
}

define(NEW_OP, 2);
define(OLD_OP, 1);
define(SAME_OP, 0);

function merge2ops($old, $new) {
    if (!$old) return array($new, FALSE, FALSE);
    if (!$new) return array($old, FALSE, FALSE);
    list($old_op, $old_remaining) = splitOp($old, $new, 1);
    list($new_op, $new_remaining) = splitOp($new, $old, 2);
    if ($old_op) $old_op->from = OLD_OP;
    if ($new_op) $new_op->from = NEW_OP;
    if ($old_op === FALSE) {
        if ($new_op === FALSE)
            return array(array($old, $new), FALSE, FALSE);
        return array($new_op, $old_remaining, $new_remaining);
    }
    if ($new_op === FALSE) {
        return array($old_op, $old_remaining, $new_remaining);
    }
    switch ($old_op->type) {
        case 'change':
            if ($new_op->type == 'change')
                return array(array($old_op, $new_op), $old_remaining, $new_remaining);
            if ($new_op->type == 'copy')
                return array($old_op, $old_remaining, $new_remaining);
            return array(array($old_op, $new_op), $old_remaining, $new_remaining);
        case 'copy':
            if ($new_op->type == 'copy')
                $new_op->from = SAME_OP;
            return array($new_op, $old_remaining, $new_remaining);
        case 'delete':
            if ($new_op->type == 'change')
                return array(array($old_op, $new_op), $old_remaining, $new_remaining);
            if ($new_op->type == 'copy')
                return array($old_op, $old_remaining, $new_remaining);
            $old_op->from = SAME_OP;
            return array($old_op, $old_remaining, $new_remaining);
    }
    return array(FALSE, $old, $new);
}

function merge3($diff, $check) {
    $merge = array();
    $conflict = FALSE;
    $op = current($diff);
    $compare = current($check);
    while (TRUE) {
        list($merged, $compare, $op) = merge2ops($compare, $op);
        if ($merged === FALSE) break;
        if (is_array($merged)) $conflict = TRUE;
        $merge [] = $merged;
        if (!$op) $op = next($diff);
        if (!$compare) $compare = next($check);
    }
    return array('merged' => $merge, 'conflict' => $conflict);
}

abstract class Action_ResolveConflict extends Doku_Action {
    public function permission_required() { return AUTH_EDIT; }

    protected function old_input_name() { return 'old'; }
    protected function new_input_name() { return 'new'; }
    abstract protected function now();
    abstract protected function merge($items);
    abstract protected function update($updated);
    protected function separators() { return $seps=array("\r\n", "\n"); }
    protected function unique_item() { return FALSE; }

    protected function split($content) {
        foreach ($this->separators() as $sep) {
            $lines = explode($sep, $content);
            if (count($lines) > 1) break;
        }
        if ($this->unique_item()) {
            sort($lines);
            $all = array();
            $last = FALSE;
            foreach ($lines as $line) {
                if ($last === FALSE)
                    $last = $lines;
                else if ($last == $line) continue;
                if ($line) $all[] = $line;
            }
            $lines = $all;
        }
        return $lines;
    }

    protected $file = NULL;
    public function handle() {
        global $ID;
        global $INPUT;
        $this->file = Projects_file::file($ID);
        $old_text = $INPUT->post->str($this->old_input_name(), '');
        $old = $this->split($old_text);
        $new_text = $INPUT->post->str($this->new_input_name(), '');
        $new = $this->split($new_text);
        $diff = new Diff($old, $new);
        $now = $this->now();
        if ($new == $now) return "show";
        if ($old != $now) {
            $check = new Diff($old, $now);
            $merged = merge3($diff->edits, $check->edits);
            if ($merged['conflict']) {
                global $MERGED_DIFF;
                $MERGED_DIFF = $merged['merged'];
                return $this->action();
            }
            $diff->edits = $merged['merged'];
            $new = $diff->closing();
        }
        lock($ID);
        $text = $this->update($this->merge($new));
        if ($text === FALSE) {
            msg('file has been changed, cannot save!', -1);
        } else saveWikiText($ID, $text, "");
        unlock($ID);
        return "show";
    }
}

function closing($op) {
    if ($op->type == 'delete') return FALSE;
    return implode("\n", $op->closing);
}

function orig($op) {
    if ($op->type == 'add') return FALSE;
    return implode("\n", $op->orig);
}

abstract class Action_ResolveConflict_Renderer extends Doku_Action_Renderer {
    abstract protected function allow_reorder_conflicts();
    protected function old_input_name() { return 'old'; }
    protected function new_input_name() { return 'new'; }

    protected $doc = NULL;
    protected $root = NULL;

    protected function createRadio($name, $values, $checked=FALSE) {
        if (is_string($values)) $value = array($values);
		$div = $this->doc->createElement('div');
		$div->setAttribute('class', 'diffradio');
        $check = $this->doc->createElement('input');
        if ($checked) $check->setAttribute('checked', 1);
        $div->appendChild($check);
        $check->setAttribute('type', 'radio');
        $check->setAttribute('name', $name);
        $check->setAttribute('value', implode('/', $values));
		$item = $this->doc->createElement('div');
		$item->setAttribute('class', 'diffradioitem');
        $div->appendChild($item);
        foreach($values as $value) {
			$box = $this->doc->createElement('div', '&nbsp;');
			$box->setAttribute('class', "bar$value");
			$item->appendChild($box);
        }
		return $div;
    }

    protected function pre($text, $pick) {
    	$empty = ($text === FALSE);
    	if ($empty) $text = '';
       	$pre = $this->doc->createElement('pre', htmlspecialchars($text));
       	if ($empty) $pre->setAttribute('class', 'diffempty');
        $pre->setAttribute('diffpick', $pick);
        return $pre;
    }

    protected function diffOpOutput($op, $index=1) {
        $div = $this->doc->createElement('div');
        $div->setAttribute('class', 'diffblock');
        $this->root->appendChild($div);
        $menu = $this->doc->createElement('div');
        $div->appendChild($menu);
        $conflict = is_array($op);
        if (!$conflict) {
            if ($op->from == OLD_OP) $op = $op->reverse();
            $orig = orig($op);
            $closing = closing($op);
            $orig_pick = 0;
            $closing_pick = 1;
        }
        else {
            $orig = closing($op[0]);
            $closing = closing($op[1]);
            $orig_pick = -1;
            $closing_pick = -1;
        }
        $radio_name = "diffaccept_$index";
        $menu->appendChild($this->createRadio($radio_name, array('old')));
        $menu->appendChild($this->doc->createTextNode(' '));
        $new_button = $this->createRadio($radio_name, array('new'), !$conflict);
        $menu->appendChild($new_button);
        $menu->appendChild($this->doc->createTextNode(' '));
        if ($conflict) {
	        $menu->appendChild($this->createRadio($radio_name, array('old', 'new')));
	        if ($this->allow_reorder_conflicts()) {
		        $menu->appendChild($this->doc->createTextNode(' '));
	            $menu->appendChild($this->createRadio($radio_name, array('new', 'old')));
        	}
    	}
        $left = $this->doc->createElement('div');
        $left->setAttribute('class', 'diffold');
        $div->appendChild($left);
        $code = $this->pre($orig, $orig_pick);
        $left->appendChild($code);
        $right = $this->doc->createElement('div', ' ');
        $right->setAttribute('class', 'diffnew');
        $div->appendChild($right);
        $code = $this->pre($closing, $closing_pick);
        $right->appendChild($code);
    }

    protected function copy($text) {
        if (!$text) return;
        $div = $this->doc->createElement('div');
        $div->setAttribute('class', 'diffcopy');
        $code = $this->pre($text, 1);
        $div->appendChild($code);
        $this->root->appendChild($div);
    }

    public function xhtml() {
        global $MERGED_DIFF;
        $this->doc = new DOMDocument;
        $this->root = $this->doc->createElement('div');
        $this->doc->appendChild($this->root);
        $text = '';
        $i = 1;
        foreach ($MERGED_DIFF as $op) {
            if (!is_array($op) && $op->from == SAME_OP) {
                $text .= orig($op);
                continue;
            }
            if ($text) {
                $this->copy($text);
                $text = '';
            }
            $this->diffOpOutput($op, $i++);
        }
        if ($text) $this->copy($text);
        echo $this->doc->saveXML($this->root);
        $form = new Doku_Form(array('id'=>'diff_form'));
        $form->addHidden($this->new_input_name(), '');
        $form->addHidden($this->old_input_name(), '');
        $form->addElement(form_makeButton('submit', $this->action(), 'save', array('id' => 'diffsubmit')));
        echo $form->getForm();
        echo cancel_button();
    }
}