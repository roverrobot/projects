<?php

require_once(dirname(__FILE__) . '/../lib/project/file.php');

function getLines($content) {
    $lines = explode("\r\n", $content);
    if (count($lines) == 1) $lines = explode("\n", $content);
    return $lines;
}

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

class Action_SaveContent extends Doku_Action {
    public function action() { return "savecontent"; }

    public function permission_required() { return AUTH_EDIT; }

    public function handle() {
        global $ID;
        global $INPUT;
        $old_text = $INPUT->post->str('old', '');
        $old = getLines($old_text);
        $new_text = $INPUT->post->str('content', '');
        $new = getLines($new_text);

        $diff = new Diff($old, $new);
        $file = Projects_file::file($ID);
        if ($file->type() != 'source') {
            return "show";
        }
        $now_text = $file->code();
        if ($now_text == $old_text)
            $content = $new_text;
        else {
            $now = getLines($now_text);
            $check = new Diff($old, $now);
            $merged = merge3($diff->edits, $check->edits);
            if ($merged['conflict']) {
                global $MERGED_DIFF;
                $MERGED_DIFF = $merged['merged'];
                return 'savecontent';
            }
            $diff->edits = $merged['merged'];
            $content = implode("\n", $diff->closing());
        }
        lock($ID);
        $pos = $file->pos();
        $from = $pos['pos'];
        $to = $from + $pos['length'];
        list($pre,$code,$suf) = rawWikiSlices("$from-$to", $ID);
        $text = $pre . DOKU_LF . $content . DOKU_LF . $suf;
        saveWikiText($ID, $text, "");
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

function diffOpOutput($doc, $root, $op) {
    $div = $doc->createElement('div');
    $div->setAttribute('class', 'diffblock');
    $root->appendChild($div);
    $menu = $doc->createElement('div');
    $div->appendChild($menu);
    if (is_subclass_of($op, _DiffOp)) {
        if ($op->from == OLD_OP) $op = $op->reverse();
        $orig = orig($op);
        $closing = closing($op);
        $orig_pick = 0;
        $closing_pick = 1;
        $check = $doc->createElement('input');
        $check->setAttribute('name', 'diffaccept');
        $check->setAttribute('type', 'checkbox');
        $check->setAttribute('checked', "1");
        $menu->appendChild($check);
    } else if (is_array($op)) {
        $orig = closing($op[0]);
        $closing = closing($op[1]);
        $orig_pick = -1;
        $closing_pick = -1;
        $check = $doc->createElement('input', 'old');
        $check->setAttribute('type', 'radio');
        $check->setAttribute('name', 'diffconflict');
        $check->setAttribute('value', 'old');
        $menu->appendChild($check);
        $menu->appendChild($doc->createTextNode(' '));
        $check = $doc->createElement('input', 'new');
        $check->setAttribute('type', 'radio');
        $check->setAttribute('name', 'diffconflict');
        $check->setAttribute('value', 'new');
        $menu->appendChild($check);
        $menu->appendChild($doc->createTextNode(' '));
        $check = $doc->createElement('input', 'old/new');
        $check->setAttribute('type', 'radio');
        $check->setAttribute('name', 'diffconflict');
        $check->setAttribute('value', 'old/new');
        $menu->appendChild($check);
        $menu->appendChild($doc->createTextNode(' '));
        $check = $doc->createElement('input', 'new/old');
        $check->setAttribute('type', 'radio');
        $check->setAttribute('name', 'diffconflict');
        $check->setAttribute('value', 'new/old');
        $menu->appendChild($check);
    }
    $left = $doc->createElement('div');
    $left->setAttribute('class', 'diffold');
    $div->appendChild($left);
    $code = $doc->createElement('pre');
    $code->setAttribute('diffpick', $orig_pick);
    $left->appendChild($code);
    if (is_string($orig)) {
        $code->appendChild($doc->createTextNode($orig));
    } else {
        $code->setAttribute('class', 'diffempty');
        $code->appendChild($doc->createTextNode(''));
    }
    $right = $doc->createElement('div', ' ');
    $right->setAttribute('class', 'diffnew');
    $div->appendChild($right);
    $code = $doc->createElement('pre');
    $code->setAttribute('diffpick', $closing_pick);
    $right->appendChild($code);
    if (is_string($closing)) {
        $code->appendChild($doc->createTextNode($closing));
    } else {
        $code->setAttribute('class', 'diffempty');
        $code->appendChild($doc->createTextNode(''));
    }
}

class Action_SaveContent_Renderer extends Doku_Action_Renderer {
    public function action() { return 'savecontent'; }

    public function xhtml() {
        global $MERGED_DIFF;
        $doc = new DOMDocument;
        $root = $doc->createElement('div');
        $doc->appendChild($root);
        $text = '';
        foreach ($MERGED_DIFF as $op) {
            if (!is_array($op) && $op->from == SAME_OP) {
                $text .= orig($op);
                continue;
            }
            if ($text) {
                $div = $doc->createElement('div');
                $div->setAttribute('class', 'diffcopy');
                $code = $doc->createElement('pre', $text);
                $code->setAttribute('diffpick', 1);
                $div->appendChild($code);
                $root->appendChild($div);
                $text = '';
            }
            diffOpOutput($doc, $root, $op);
        }
        echo $doc->saveXML($root);
        $form = new Doku_Form(array('id'=>'diff_form'));
        $form->addHidden('content', '');
        $form->addHidden('old', '');
        $form->addElement(form_makeButton('submit', 'savecontent', 'save', array('id' => 'diffsubmit')));
        $form->addElement(form_makeButton('cancel', 'show', 'cancel'));
        echo $form->getForm();
    }
}