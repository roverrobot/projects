<?php

class Projects_Maker_Media extends Projects_Maker
{
    public function name() { return "media"; }

    public function can_handle($file) {
        if ($file->code()) return FALSE;
        $path = mediaFN($file->id());
        return file_exists($path);
    }

    public function make($file) {
        $media = mediaFN($file->id());
        $path = $file->file_path();

        if (file_exists($path)) unlink($path);
        symlink($media, $path);
        return true;
    }

    public function auto_dependency($file) {
        return array();
    }
}
