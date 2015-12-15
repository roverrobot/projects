<?php

define(DOKU_INC, dirname(__FILE__) . '/../../../../');
define(DOKU_CONF, DOKU_INC . 'conf/');

$opts = getopt('', array('id:', 'remake:', 'baseurl:', 'sectok:', 'user:', 'group:'));
if (!isset($opts['id'])) exit();
$id = $opts['id'];
if (!$id) exit();
$remake = (isset($opts['remake'])) ? $opts['remake'] : FALSE;

if (!isset($opts['baseurl'])) exit();
define(DOKU_URL, $opts['baseurl']);
define(DOKU_REL, $opts['baseurl']);

require_once dirname(__FILE__) .'/project/file.php';
require_once DOKU_INC . '/inc/init.php';

if (!isset($opts['sectok'])) exit();
if (!checkSecurityToken($opts['sectok'])) exit();

$user = (!isset($opts['user'])) ? '' : $opts['user'];
$group = (!isset($opts['group'])) ? '' : $opts['group'];
$group = explode(':', $group);
if (auth_aclcheck($id, $user, $group) < DOKU_EDIT) exit();
$file = Projects_file::file($id);
if ($file->is_making()) return;
$result = $file->make(array(), $remake);
if (is_numeric($result))
	copy($file->file_path(), mediaFN($file->id()));
