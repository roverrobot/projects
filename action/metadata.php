<?php
/**
 * projects Action Plugin: hajacking the modification of metadata
 * it cleears the persistent metadata 'projectfile' if the file in the page was removed.
 *
 * @author     Junling Ma <junlingm@gmail.com>
 */
 
require_once DOKU_PLUGIN.'action.php';
require_once dirname(__FILE__) . '/../lib/syntax/file.php';

class action_plugin_projects_metadata extends DokuWiki_Action_Plugin { 
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('PARSER_METADATA_RENDER', 'BEFORE', $this,
                                   'toRender');
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this,
                                   'rendered');
    }

    function toRender(&$event, $param) {
        global $OLD_PROJECTS_FILE;
        $id = $event->data['page'];
        if (isset($event->data['current']['projectfile']))
            $OLD_PROJECTS_FILE = Projects_file::file($id, $event->data['current']['projectfile']);
        else $OLD_PROJECTS_FILE = NULL;
    }

	// the matedata has been rendered 
    function rendered(&$event, $param) {
        if (isset($event->data['persistent']['projectfile']))
            unset($event->data['persistent']['projectfile']);
        if (isset($event->data['current']['projectfile']) && $event->data['current']['projectfile'])
            return;
        global $OLD_PROJECTS_FILE;
        if ($OLD_PROJECTS_FILE) $OLD_PROJECTS_FILE->rm();
	}
		
}
