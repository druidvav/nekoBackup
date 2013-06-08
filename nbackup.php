<?php
define('CONFIG_PATH', dirname(__FILE__) . '/config/');
define('SOURCE_PATH', dirname(__FILE__) . '/source/');
define('VENDOR_PATH', dirname(__FILE__) . '/vendor/');
define('BUNDLE_PATH', dirname(__FILE__) . '/bundle/');
define('S3CMD_PATH',  dirname(__FILE__) . '/bundle/s3cmd/');
define('LOG_PATH',    '/var/log/nbackup.log');

include_once(VENDOR_PATH . 'autoload.php');

echo "  >>  nekoBackup 1.3alpha by druidvav  << \n";
echo "\n";

$opts = getopt('', array('driver:', 'initial', 'install'));

//if(isset($opts['install']))
//{
//  echo "Installing crontab...";
//
//  $line = "0 3 * * * php " . __FILE__ . " --driver={$opts['driver']} &> /dev/null\n";
//  `(crontab -l; echo "{$line}") | crontab -`;
//
//  echo " done.\n";
//  exit;
//}

use nekoBackup\Config;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher();

$basic = new \nekoBackup\BasicDriver\Driver($dispatcher);

switch(@$opts['driver'])
{
  case 's3':
    nekoBackup\BackupLogger::append('Using [Amazon S3] storage driver.');
    $s3driver = new nekoBackup\S3Driver\Driver($dispatcher);
    break;
}

if(isset($opts['initial'])) {
  nekoBackup\BackupLogger::append('Running [initial] backup mode.');
}

$backup = nekoBackup\Backup::get(Config::get('schedule'));
$backup->execute(isset($opts['initial']) ? 'initial' : time());