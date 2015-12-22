<?php

class action_plugin_projects_render extends DokuWiki_Action_Plugin
{
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this,
                                   'render');
    }

    private function show() {
        global $REV;
        global $ID;
        $path = wikiFN($ID);
        if (file_exists($path) || $REV) return FALSE;

        ob_start();
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
        trigger_event('TPL_CONTENT_DISPLAY', $html_output, 'ptln');
        return TRUE;
    }

    function render(&$event, $param) {
        if ($event->data == 'show' && $this->show())
            $event->preventDefault();
    }
}
