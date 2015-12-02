<?php

require_once(dirname(__FILE__) . '/resolve_conflict.php');


class Action_UpdateDependency extends Action_ResolveConflict {
    public function action() { return "update_dependency"; }

    protected function now() {
        if (!$this->file) return array();
        return $this->file->dependency();
    }

    protected function separators() { return $seps=array("\r\n", "\n", "\r", ';'); }
    protected function unique_item() { return TRUE; }

    protected function merge($dependencies) {
        sort($dependencies);
        return implode(';', $dependencies);
    }

    protected function update($use) {
        global $ID;
        if (!$this->file) return FALSE;
        $entertag = $this->file->entertag();
        $from = $entertag['pos'];
        $to = $from + $entertag['length'];
        list($pre,$enter,$suf) = rawWikiSlices("$from-$to", $ID);
        $enter .= '</source-file>';
        $dom = DOMDocument::loadXML($enter);
        if ($dom === FALSE) return FALSE;
        $dom->documentElement->setAttribute('use', $use);
        $enter = $dom->saveXML($dom->documentElement);
        $enter = substr($enter, 0, -2) . '>';
        return $pre . $enter . $suf;
    }
}

class Action_UpdateDependency_Renderer extends Action_ResolveConflict_Renderer {
    public function action() { return 'update_dependency'; }
    protected function allow_reorder_conflicts() { return FALSE; }
}