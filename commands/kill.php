<?php

require_once dirname(__FILE__) . '/../lib/project/file.php';

class Action_Make extends Doku_Action {
    public function action() { return "kill"; }

    public function permission_required() { return AUTH_EDIT; }

    public function handle() {
        global $INPUT;
        global $ID;
        if ($INPUT->has('id'))
            $id = $INPUT->str('id');
        else $id = $ID;

        $file = Projects_file::file($id);
        if ($file->is_making()) {
            system('kill ' . $file->status()->pid());
            $file->killed();
        }
        return "show";
    }
}
