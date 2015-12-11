<?php
/**
 * The syntax plugin to handle <source-file> tags
 *
 */

require_once dirname(__FILE__) . '/../lib/syntax/file.php';
require_once dirname(__FILE__) . '/../lib/editor.php';
require_once dirname(__FILE__) . '/../lib/formatter.php';

class syntax_plugin_projects_generated extends syntax_projectfile
{
    protected function type() { return 'generated'; }

    private static function part(&$time, $count, $unit) {
        $div = floor($time/$count);
        $val = round($time - $div*$count);
        $time = $div;
        if ($val == 0) return '';
        $result = $val . $unit;
        if ($val>1) return $result . 's';
        return $result;
    }
 
    private static function format_time($time) {
        $sec = self::part($time, 60, ' second');
        $min = self::part($time, 60, ' minute');
        $hour = self::part($time, 24, ' hour');
        $day = self::part($time, 7, ' day');
        $week = self::part($time, $time+1, ' week');
        return "$week $day $hour $min $sec";
    }

    protected function content($file) {
        if ($file->is_making()) {
            $time = time() - $file->status()->started();
            $content = '<div id="PROJECTS_progress">The file has been generating for ' . self::format_time($time) . 
                ': ' . kill_button($file->id(), FALSE) . DOKU_LF;
            foreach($file->status()->made() as $made) 
                $content .= '<div class="success">' . html_wikilink($made) . '</div>' . DOKU_LF;
            $content .= '<div class="notify">' . html_wikilink($file->status()->making()) . '</div>' . DOKU_LF;
            foreach($file->status()->queue() as $queue) 
                $content .= '<div class="info">' . html_wikilink($queue) . '</div>' . DOKU_LF;
            return $content . '</div>' . DOKU_LF; 
        }
        if (is_array($file->status())) {
            $content = '<div>Error in file generation:' . DOKU_LF;
            foreach($file->status() as $id => $errors)
                foreach ($errors as $error)
                    $content .= '<div class="error">' . html_wikilink($id) . ': ' . $error . '</div>' . DOKU_LF;
            return $content .  '</div>' . DOKU_LF;
        }
        if ($file->status() === PROJECTS_MODIFIED)
            return '<div>The file is not generated yet: ' . make_button($file->id(), FALSE) . '</div>'; 
        $content = Projects_formatter::xhtml($file);
        return $content;
    }

    protected function createTabs($file) {
        parent::createTabs($file);
        $summary = $this->tabs->tab('Summary');
        if ($file->is_making())
            $summary->newAction(kill_button($file->id()));
        else $summary->newAction(make_button($file->id(), $file->status() == PROJECTS_MADE));
        $summary->setContent($this->content($file));
        $recipe = new Projects_RecipeTab($this->tabs, $file, $this->read_only());
        $this->tabs->newTab($recipe);
        $log = new Projects_LogTab($this->tabs, $file);
        $this->tabs->newTab($log);
    }

}
