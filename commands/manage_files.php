<?php

require_once dirname(__FILE__) . '/../lib/project/file.php';
require_once dirname(__FILE__) . '/../lib/syntax/xhtml.php';

class Action_Manage_Files extends Doku_Action {
    public function action() { return "manage_files"; }

    public function permission_required() { return AUTH_READ; }

    public function handle() { }
}

class Render_Manage_File extends Doku_Action_Renderer {
    public function action() { return "manage_files"; }

    public function xhtml() {
        global $ID;
        $ns = getNS($ID);
        list($files, $subprojects) = Projects_file::project_files($ns);

        $generated = array();
        $source = array();
        foreach ($files as $id => $file) {
            if ($file->type() == 'source')
                $source[$id] = $file;
            elseif ($file->type() == 'generated')
                $generated[$id] = $file;
        }

        ksort($generated);
        ksort($source);
        sort($subprojects);

        echo '<h1>Source files</h1>' . DOKU_LF;
        echo '<ul>' . DOKU_LF;
        echo '<li>' . create_button($ID, 'source') . '</li>' . DOKU_LF;
        foreach ($source as $id => $file) {
            echo '<li>' . html_wikilink($id) . ': ' .
                download_button($id) . ', ' .
                delete_button($id) .
                '</li>' . DOKU_LF;
        }
        echo '</ul>' . DOKU_LF;

        echo '<h1>Generated files</h1>' . DOKU_LF;
        echo '<ul>' . DOKU_LF;
        echo '<li>' . create_button($ID, 'generated') . '</li>' . DOKU_LF;
        foreach ($generated as $id => $file) {
            $make = make_button($id, $file->status() == PROJECTS_MADE);
            echo '<li>' . html_wikilink($id) . ': ' .
                download_button($id) . ', ' .
                delete_button($id) . ', ' . $make .
                '</li>' . DOKU_LF;
        }
        echo '</ul>' . DOKU_LF;

        echo '<h1>Subprojects</h1>' . DOKU_LF;
        echo '<ul>' . DOKU_LF;
        echo '<li>' . create_button($ID, 'project') . '</li>' . DOKU_LF;
        foreach ($subprojects as $sub) {
            echo '<li><a href="' . wl($sub.':', array('do' => 'manage_files')). 
                '">' . noNS($sub) . '</a></li>' . DOKU_LF;
        }
        echo '</ul>' . DOKU_LF;

        if ($ns) {
            $name = getNS($ns);
            $id = $name . ':';
            if (!$name) {
                $id = '/';
                $name = '/ (root)';
            }
            echo '<h1>Parent projects</h1>' . DOKU_LF;
            echo '<ul><li><a href="' . wl($id, array('do' => 'manage_files')) .
                '">' . $name . '</a></li></ul>' . DOKU_LF;
        }
    }
}
