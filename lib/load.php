<?php

// This is a utility function to check if a class is abstract
// abstract component classes will not be initialized.
function is_abstract_class($class) {
    $ref_class = new ReflectionClass($class);
    $abs = $ref_class->isAbstract();
    unset($ref_class);
    return $abs;
}

function load_dir($dir, $name=false) {
    if (!is_dir($dir)) return;

    // read the entrys of $dir one by one
    $dh = dir($dir);
    if ($name && strtolower(substr($name, -4)) != '.php') $name .= '.php';
    $subdirs = array();
    while (false !== ($entry = $dh->read())) {
        // skip hidden files
        if ($entry[0] == '.') continue;
        $path = $dir . '/' . $entry;
        if (is_dir($path)) {
            array_push($subdirs, $path);
            continue;
        }

        if (strtolower(substr($entry, -4)) != '.php') continue;

        if (!$name || strtolower($entry) == $name)
            include_once($dir . '/' . $entry);
    }
    $dh->close();

    // load scripts in subdirs recursively
    foreach ($subdirs as $subdir) load_dir($subdir, $action);
}