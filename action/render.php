<?php

require_once DOKU_PLUGIN . 'action.php';

class action_plugin_projects_render extends DokuWiki_Action_Plugin
{
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this,
                                   'render');
    }

    function render(&$event, $param) {
        $action = $event->data;
        if (substr($action, 0, 12) == 'DOKU_ACTION_')
            $action = substr($action, 12);
        if (Doku_Action::render($action))
            $event->preventDefault();
    }
}
