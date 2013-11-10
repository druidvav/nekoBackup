<?php
define('VERSION', '2.0alpha');
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') . '/');

define('EXECUTABLE',  ROOT_PATH . 'sbin/nbackup');
define('CONFIG_FILE', ROOT_PATH . 'etc/nbackup.yaml');
define('LOG_PATH',    ROOT_PATH . 'log/summary.log');
define('ERRLOG_PATH', ROOT_PATH . 'log/error.log');
define('TMP_PATH',    ROOT_PATH . 'tmp/');
define('SOURCE_PATH', ROOT_PATH . 'source/');
define('VENDOR_PATH', ROOT_PATH . 'vendor/');

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('log_errors', 'On');
ini_set('error_log', ERRLOG_PATH);

include_once(VENDOR_PATH . 'autoload.php');
include_once(SOURCE_PATH . 'Console.php');