<?php

class Doku_Action_Renderer_Show extends Doku_Action_Renderer
{
    public function action() { return "show"; }

    public function xhtml() {
        global $REV;
        global $ID;
        $path = wikiFN($ID);
        if (file_exists($path) || $REV) return false;
        echo '<h1>Page "' . noNS($ID) . '" Does Not Exist</h1>' . DOKU_LF;
        echo '<ul><li>Create a:</li>' .DOKU_LF;
        echo '<ul>' .DOKU_LF;
        echo '<li><a href="' .
            wl($ID, array('do' => 'create', 'type' => 'source')) . '">' .
            'source file</a></li>' . DOKU_LF;
        echo '<li><a href="' .
            wl($ID, array('do' => 'create', 'type' => 'generated')) . '">' .
            'generated file</a></li>' . DOKU_LF;
        echo '<li><a href="' .
            wl($ID, array('do' => 'edit')) . '">' .
            'plain wiki page</a></li>' . DOKU_LF;
        echo '</ul>'.DOKU_LF;
        echo '<li>Manage <a href="' . wl($ID, array('do'=>'manage_files')) .
            '">other files</a></li>' . DOKU_LF;
        echo '</ul>'.DOKU_LF;
        return true;
    }
}
