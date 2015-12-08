<?php
/**
 * projects Action Plugin: hijack the page write events
 *
 * @author     Junling Ma <junlingm@gmail.com>
 */
 
require_once DOKU_PLUGIN.'action.php';
require_once dirname(__FILE__) . '/../lib/project/file.php';

class action_plugin_projects_page extends DokuWiki_Action_Plugin { 
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this,
                                   'toWrite');
    }

    /**
     * intercept page deletion
     *
     */
    function toWrite(&$event, $param) {
		$rev = $event->data[3];
		// only handle the event if it is the current revision
		if ($rev) return;

		$content = $event->data[0][1];
		if ($content) return;

		$namespace = $event->data[1];
		$name = $event->data[2];
		if ($namespace)
		    $id = $namespace . ':' . $name;
		else $id = $name;
        Projects_file::remove($id);
	}	
}
