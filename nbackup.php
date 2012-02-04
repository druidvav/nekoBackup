<?php
define('CONFIG_PATH', dirname(__FILE__) . '/config/');
define('SOURCE_PATH', dirname(__FILE__) . '/source/');
define('BUNDLE_PATH', dirname(__FILE__) . '/bundle/');
define('S3CMD_PATH',  dirname(__FILE__) . '/bundle/s3cmd/');

include_once(SOURCE_PATH . 'Backup.php');

echo "  >>  nekoBackup 1.1b2 by druidvav  << \n";
echo "\n";

$opts = getopt('', array('driver:', 'initial'));

if(!is_file(CONFIG_PATH . 'config.yaml'))
  die('File "' . CONFIG_PATH . 'config.yaml" must be present');

switch(@$opts['driver'])
{
  case 's3':
    BackupLogger::append('Using [Amazon S3] storage driver.');

    if(!is_file(CONFIG_PATH . 's3.yaml'))
      die('File "' . CONFIG_PATH . 's3.yaml" must be present');
      
    if(!is_file(S3CMD_PATH . 's3cmd'))
    {
      BackupLogger::append('File "' . S3CMD_PATH . 's3cmd" not found...');

      if(is_file(BUNDLE_PATH . 's3cmd-1.0.1-mini.tar.gz'))
      {
        BackupLogger::append(' ..installing..');
        if(!is_dir(S3CMD_PATH)) mkdir(S3CMD_PATH);
        exec("tar -xzf " . BUNDLE_PATH . "s3cmd-1.0.1-mini.tar.gz -C " . S3CMD_PATH);

        if(!is_file(S3CMD_PATH . 's3cmd'))
        {
          BackupLogger::append(' ..failed!');
          die(1);
        }
        else
        {
          BackupLogger::append(' ..done!');
        }
      }
      else
      {
        BackupLogger::append(' ..cannot install!');
        die(1);
      }
    }

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

$backup = Backup::get(dirname(__FILE__) . '/config/config.yaml');
$backup->execute(isset($opts['initial']) ? 'initial' : time());