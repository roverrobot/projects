<?php

class Action_Create extends Doku_Action {
    public function action() { return "create"; }

    public function permission_required() { return AUTH_CREATE; }

    public function handle() {
        global $ID;
        global $INPUT;
        if ($INPUT->has('New')) {
            $name = $INPUT->str('New');
            if (!$name) return 'manage_files';
            $ID = cleanID(getNS($ID) . ':' . $name);
        }

        $path = wikiFN($ID);
        if (file_exists($path)) return 'show';

        global $TEXT;
        $type = strtolower($INPUT->str('type'));
        switch ($type) {
            case 'generated':
                $TEXT = '<generated-file>' . DOKU_LF . 
                    '</generated-file>';
                break;
            case 'source':
                $TEXT = '<source-file>' . DOKU_LF .
                    '</source-file>';
                break;
            case 'project':
                $ID .= ':';
                return 'show';
            default: return 'edit';
        }

        saveWikiText($ID, $TEXT, "Created");
        unlock($ID);

        global $INFO;
        $INFO['exists'] = true;

        return "show";
    }
}
