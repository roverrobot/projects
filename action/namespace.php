<?php
/**
 * projects Action Plugin: manage the projects namespace
 *
 * @author     Junling Ma <junlingm@gmail.com>
 */
 
 require_once dirname(__FILE__) .  '/../lib/project/file.php';
require_once DOKU_PLUGIN.'action.php';

class action_plugin_projects_namespace extends DokuWiki_Action_Plugin {
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('IO_NAMESPACE_DELETED', 'AFTER', $this,
                                   'deleted');
    }
 
    // recursively delete all the files
    private function delete_dir($dir) {
        if (!file_exists($dir) || !is_dir($dir)) return;
        $dh = dir($dir);
        if ($dh) {
            while (($file = $dh->read()) !== false) {
                if ($file === '.' || $file === '..') continue;
                $file = $dir . $file;
                if (is_dir($file))
                    $this->delete_dir($file . '/');
                else unlink($file);
            }
            $dh->close();
        }
        rmdir($dir);
    }

    /**
     * a namespace has been deleted
     *
     */
    function deleted(&$event, $param) {
    	$ns = $event->data[0];
    	$path = Projects_file::projects_file_path($ns);
    	$this->delete_dir($path);
	}	
}
