<?php

class Projects_Maker_Bash extends Projects_Maker
{
    protected $bash = '';

    public function __construct() {
        $this->bash = find_executable('bash');
    }

    public function name() { return "bash"; }

    public function can_handle($file) {
        return $this->bash && $file->code() != '';
    }

    public function make($file) {
        $code = $file->code();

        $line = strtok($code, "\n");
        if ($line && substr($line, 0, 2) == '#!') {
            $command = trim(substr($line, 2));
            $code = trim(substr($code, strlen($line) + 1));
        } else $command = $this->bash . ' -l';

        $log = $file->log_file();
        $descs = array(
            array('pipe', 'r'),
            array('file', $log, 'w'),
            array('file', $log, 'a'));
        $proc = proc_open($command, $descs, $pipes, dirname($log));
        fwrite($pipes[0], $code);
        $return = proc_close($proc);
        return $return === 0;
    }
}
