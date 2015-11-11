<?php

require_once dirname(__FILE__) . '/../lib/action.php';

require_once DOKU_PLUGIN . 'action.php';

class action_plugin_projects_action extends DokuWiki_Action_Plugin
{
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this,
                                   'act');
    }

    function act(&$event, $param) {
        if (Doku_Action::act($event->data)) {
            $event->data = 'DOKU_ACTION_' . $event->data;
            $event->preventDefault();
        }
    }
}
