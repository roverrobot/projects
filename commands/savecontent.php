<?php

require_once(dirname(__FILE__) . '/resolve_conflict.php');

class Action_SaveContent extends Action_ResolveConflict {
    public function action() { return "savecontent"; }

    protected function now() {
        if (!$this->file) return array();
        return $this->split($this->file->code());
    }

    protected function merge($lines) {
        return implode("\n", $lines);
    }

    protected function update($content) {
        global $ID;
        if (!$this->file) return FALSE;
        $pos = $this->file->pos();
        $from = $pos['pos'];
        $to = $from + $pos['length'];
        list($pre,$code,$suf) = rawWikiSlices("$from-$to", $ID);
        return $pre . $content . $suf;
    }
}

class Action_SaveContent_Renderer extends Action_ResolveConflict_Renderer {
    public function action() { return 'savecontent'; }
    protected function allow_reorder_conflicts() { return TRUE; }
}