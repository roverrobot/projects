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
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this,
                                   'rendered');
    }

	// the matedata has been rendered 
    function rendered(&$event, $param) {
        $id = $event->data['page'];
        if (syntax_projectfile::called()) return;

        $file = Projects_file::file($id, $event->data['persistent']['projectfile']);
        if (!$file) return;
        unset($event->data['persistent']['projectfile']);
        unset($event->data['current']['projectfile']);
        $file->rm();
	}
		
}
