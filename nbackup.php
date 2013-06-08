<?php
define('VERSION', '1.3rc1');
define('CONFIG_PATH', dirname(__FILE__) . '/config/');
define('SOURCE_PATH', dirname(__FILE__) . '/source/');
define('VENDOR_PATH', dirname(__FILE__) . '/vendor/');
define('LOG_PATH',    '/var/log/nbackup.log');

include_once(VENDOR_PATH . 'autoload.php');
include_once(SOURCE_PATH . 'Console.php');

