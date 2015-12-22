<?php

define(PROJECTS_RUNMAKE, dirname(__FILE__) . '/../lib/runmake.php');
require_once dirname(__FILE__) . '/../lib/maker.php';

class Action_Make extends Doku_Action {
    protected static $PHP = '';

    public static function findPHP() {
        self::$PHP = Projects_Maker::find_executable('php');
    }

    public function action() { return "make"; }

    public function permission_required() { return AUTH_EDIT; }

    public function handle() {
        if (!self::$PHP)
            return 'show';
        global $INPUT;
        global $ID;
        global $USERINFO;
        if ($INPUT->has('id'))
            $id = $INPUT->str('id');
        else $id = $ID;

        if ($INPUT->has('remake'))
            $remake = $INPUT->bool('remake');
        else $remake = FALSE;
        $remake = $remake?1:0;

        if ($INPUT->has('sectok'))
            $sectok = $INPUT->str('sectok');
        else $sectok = '';

        $user = $INPUT->server->str('REMOTE_USER');
        $grp = implode(':', $USERINFO['grps']);
        $command = self::$PHP . ' ' . PROJECTS_RUNMAKE . 
            " --baseurl='" . DOKU_BASE . "'" .
            " --id=$id --remake=$remake";
        if ($user) $command .= " --user='$user'";
        if ($grp) $command .= " --group='$grp'";
        if ($sectok) $command .= " --sectok='$sectok'";
//        $command .= ' 2>&1 > /tmp/error';
        $command .= ' &';
        system($command);
        return "show";
    }
}

Action_Make::findPHP();