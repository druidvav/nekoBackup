<?php
define('CONFIG_PATH', dirname(__FILE__) . '/config/');
define('SOURCE_PATH', dirname(__FILE__) . '/source/');
define('BUNDLE_PATH', dirname(__FILE__) . '/bundle/');
define('S3CMD_PATH',  dirname(__FILE__) . '/bundle/s3cmd/');
define('LOG_PATH',    '/etc/nbackup.log');

include_once(SOURCE_PATH . 'Backup.php');

echo "  >>  nekoBackup 1.1b2 by druidvav  << \n";
echo "\n";

$opts = getopt('', array('driver:', 'initial', 'install'));

if(isset($opts['install']))
{
  echo "Installing crontab...";

  $line = "0 3 * * * php " . __FILE__ . " --driver={$opts['driver']}";
  `(crontab -l; echo "{$line}") | crontab -`;

  echo " done.\n";
  exit;
}

switch(@$opts['driver'])
{
  case 's3':
    BackupLogger::append('Using [Amazon S3] storage driver.');
    BackupS3::init(CONFIG_PATH . 's3.yaml');
    break;
  default:
    BackupLogger::append('Using default storage driver.');
    break;
}

if(isset($opts['initial']))
{
  BackupLogger::append('Running [initial] backup mode.');
}

$backup = Backup::get(CONFIG_PATH . 'config.yaml');
$backup->execute(isset($opts['initial']) ? 'initial' : time());