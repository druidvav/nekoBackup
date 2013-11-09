<?php
define('VERSION', '2.0alpha');
define('CONFIG_FILE', dirname(__FILE__) . '/etc/nbackup.yaml');
define('SOURCE_PATH', dirname(__FILE__) . '/source/');
define('VENDOR_PATH', dirname(__FILE__) . '/vendor/');
define('EXECUTABLE',  __FILE__);
define('LOG_PATH',    dirname(__FILE__) . '/log/summary.log');
define('ERRLOG_PATH', dirname(__FILE__) . '/log/error.log');

error_reporting(E_ALL);
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
ini_set('error_log', ERRLOG_PATH);

include_once(VENDOR_PATH . 'autoload.php');
include_once(SOURCE_PATH . 'Console.php');
