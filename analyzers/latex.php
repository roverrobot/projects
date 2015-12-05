<?php

class Analyzer_Latex extends Projects_Analyzer {
    /**
     * The name of the parser, a human readable string, a unique identifier
     */
    public function name() { return "latex"; }
    
    /**
     * whether this parser can make a given target
     */
    public function can_handle($file) {
        $ext = strtolower($file->file_extension());
        return in_array($ext, array('tex', 'ltx')); 
    }

    /** 
     * The files used in this file
     */
    public function analyze($file) {
        $content = $this->remove_comments($file->content());
        $ns = getNS($id);
        $inputs = $this->find_command($ns, 'input', $content);
        $includes = $this->find_command($ns, 'include', $content);
        $graphs = $this->find_command($ns, 'includegraphics', $content, ".pdf");
        $bibs = $this->find_command($ns, 'bibliography', $content, ".bib");
        return array_merge($inputs, $includes, $graphs, $bibs);
    }    

    private function remove_comments($content) {
        return preg_replace('/%.*?$/m', '', $content);
    }

    private function match_command($command, $content) {
        $parameters = '(?i:\[.*?\])?';
        $pattern = "/\\\\$command *$parameters *\{ *(?P<content>.*?) *\}/";
        $matched = preg_match_all($pattern, $content, $matches);
        if ($matched == 0) return NULL;
        return $matches;
    }

    function find_command($ns, $command, $content, $file_extension = ".tex") {
        $deps = array();
        $matches = $this->match_command($command, $content);
        if ($matches == NULL) return array();
        foreach ($matches['content'] as $match) {
            if (strtolower(substr($match, -strlen($file_extension))) != 
                $file_extension)
                $match .= $file_extension;
            $id = self::absoluteID($ns, $match);
            $deps[$id] = $match;
        }
        return $deps;
    }
}
