<?php

define(MAKER_ROOT, dirname(__FILE__) . '/../makers/');

require_once dirname(__FILE__) . '/analyzer.php';

class Projects_Maker_Manager extends Doku_Component_Manager {
    private $makers = array();
    private static $manager = NULL;

    public static function manager() {
        if (!self::$manager)
            self::$manager = new Projects_Maker_Manager;
        return self::$manager;
    }

    protected function handle($class) {
        if (is_subclass_of($class, 'Projects_Maker')) {
            $handler = new $class;
            $this->makers[$handler->name()] = $handler;
        }
    }

    public function maker($request) {
        if (is_string($request)) {
            if (!isset(self::$_handlers[$request])) return FALSE;
            return self::$_handlers[$request];
        }
        $handlers = array();
        if (is_a($request, 'Projects_file')) {
            foreach ($this->makers as $handler)
                if ($handler->can_handle($request))
                    array_push($handlers, $handler);
        }
        return $handlers;
    }

    public function __construct() {
        $this->load(MAKER_ROOT);
    }
}

abstract class Projects_Maker {

    static public function find_executable($name, $extra_searchpaths = array()) {
        if (is_string($extra_searchpaths))
            $extra_searchpaths = explode(PATH_SEPARATOR, $extra_searchpaths);
        $paths = array_merge(explode(PATH_SEPARATOR, getenv('PATH')), $extra_searchpaths);
        $exe = (isset($_SERVER["WINDIR"])) ? $name . '.exe' : $name;

        // add /usr/local/bin
        if (!isset($_SERVER["WINDIR"]) && !in_array('/usr/local/bin', $paths))
            $paths[] = '/usr/local/bin';
        foreach ($paths as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $exe;
            if (file_exists($file) && is_file($file))
                return $file;
        }

        // check if it is in the PATH defined in bash_rc
        if (!isset($_SERVER["WINDIR"]))
            return trim(shell_exec("bash -l -c 'which $exe'"));
        return FALSE;
    }

    protected function run($file, $command, $code=FALSE) {
        $dir = dirname(explode(' ', $command)[0]);
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        $env = NULL;
        if (!in_array($dir, $paths)) {
            $paths[] = $dir;
            $env = array('PATH' => implode(PATH_SEPARATOR, $paths));
        }
        $log = $file->log_file();
        $descs = array(
            array('pipe', 'r'),
            array('file', $log, 'w'),
            array('file', $log, 'a'));
        $proc = proc_open($command, $descs, $pipes, dirname($log), $env);
        if ($code) fwrite($pipes[0], $code);
        $return = proc_close($proc);
        return $return === 0;
    }

    static public function dependence($id) {
        $file = Projects_file::file($id);
        if ($file) return $file;
        $file = new Projects_file_generated($id);
        $file->analyze();
        if (!$file->maker()) return NULL;
        return $file;
    }

    abstract public function name();
    abstract public function can_handle($file);
    abstract public function auto_dependency($file); 
    abstract public function make($file);
}

