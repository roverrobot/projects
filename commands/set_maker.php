<?php

require_once(dirname(__FILE__) . '/resolve_conflict.php');


class Action_SetMaker extends Action_ResolveConflict {
    public function action() { return "set_maker"; }

    protected function now() {
        if (!$this->file) return array();
        $maker = $this->file->maker();
        if ($maker) return array($maker);
        return array();
    }

    protected function separators() { return $seps=array("\r\n", "\n", "\r"); }
    protected function unique_item() { return TRUE; }

    protected function merge($maker) {
        return $maker[0];
    }

    protected function update($maker) {
        global $ID;
        if (!$this->file) return FALSE;
        $entertag = $this->file->entertag();
        $from = $entertag['pos'];
        $to = $from + $entertag['length'];
        list($pre,$enter,$suf) = rawWikiSlices("$from-$to", $ID);
        $enter .= '</' . $this->file->type() . '-file>';
        $dom = DOMDocument::loadXML($enter);
        if ($dom === FALSE) return FALSE;
        $dom->documentElement->setAttribute('maker', $maker);
        $enter = $dom->saveXML($dom->documentElement);
        $enter = substr($enter, 0, -2) . '>';
        return $pre . $enter . $suf;
    }
}

class Action_SetMaker_Renderer extends Action_ResolveConflict_Renderer {
    public function action() { return 'set_maker'; }
    protected function allow_reorder_conflicts() { return FALSE; }
}