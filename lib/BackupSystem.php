<?php
class BackupSystem
{
  public static function execute($config, $period)
  {
    if($config['period'] != $period)
    {
      BackupLogger::append("skipping ({$config['period']})", 1);
    }
    elseif($config['packages'] == 'Debian')
    {
      BackupLogger::append("reading packages for Debian..", 1);
      $filename = Backup::get()->prepareFilename('system-packages', 'txt.bz2');
      `dpkg --get-selections | grep -v deinstall | bzip2 --best > {$filename}`;
      BackupEvents::trigger('file', array('filename' => $filename));
      BackupLogger::append(" ..done", 1);
    }
    else
    {
      BackupLogger::append("unknown packaging mode: {$config['packages']}", 3);
    }
  }
}