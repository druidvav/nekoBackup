<?php
include_once(dirname(__FILE__) . '/lib/Backup.php');

echo "  >>  nekoBackup 1.1b1 by druidvav  << \n";
echo "\n";

$opts = getopt('', array('driver:', 'initial'));

switch(@$opts['driver'])
{
  case 's3':
    BackupLogger::append('Using [Amazon S3] storage driver.');

    if(!is_file(dirname(__FILE__) . '/cfg/s3.yaml'))
      die('File "cfg/s3.yaml" must be present');

    if(!is_file(dirname(__FILE__) . '/cfg/s3cfg'))
      die('File "cfg/s3cfg" must be present');

    BackupS3::init(dirname(__FILE__) . '/cfg/s3.yaml');
    break;
  default:
    BackupLogger::append('Using default storage driver.');
    break;
}

if(isset($opts['initial']))
{
  BackupLogger::append('Running [initial] backup mode.');
}

$backup = Backup::get(dirname(__FILE__) . '/cfg/config.yaml');
$backup->execute(isset($opts['initial']) ? 'initial' : time());