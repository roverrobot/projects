<?php

class Analyzer_C extends Projects_Analyzer {
    /**
     * The name of the parser, a human readable string, a unique identifier
     */
    public function name() { return "c"; }
    
    /**
     * whether this parser can make a given target
     */
    public function can_handle($file) {
        $ext = strtolower($file->file_extension());
        return in_array($ext, array('c', 'cc', 'cpp', 'cxx', 'h', 'hpp'));
    }

    /** 
     * The files used in this file
     */
    public function analyze($file) {
        $content = $this->remove_comments($file->content());
        $ns = getNS($id);
        return $this->find_include($ns, 'include', $content);
    }    

    private function remove_comments($content) {
        $content = preg_replace('#//.*?$#m', '', $content);
        return preg_replace('#/\*.*?\*/#m', '', $content);
    }

    private function match_include($command, $content) {
        $pattern = '/#' . $command . ' *" *(?P<content>.*?) *"/';
        $matched = preg_match_all($pattern, $content, $matches);
        if ($matched == 0) return NULL;
        return $matches;
    }

    function find_include($ns, $command, $content) {
        $deps = array();
        $matches = $this->match_include($command, $content);
        if ($matches == NULL) return array();
        foreach ($matches['content'] as $match) {
            $id = self::absoluteID($ns, $match);
            $deps[$id] = $match;
        }
        return $deps;
    }
}
