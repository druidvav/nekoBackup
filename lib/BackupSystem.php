<?php
class BackupSystem
{
  public static function execute(Backup &$parent, $config)
  {
    if($config['period'] != $parent->period)
    {
      $parent->log("skipping ({$config['period']})", 1);
    }
    elseif($config['packages'] == 'Debian')
    {
      $parent->log("reading packages for Debian..", 1);
      $filename = $parent->prepareFilename('system-packages', 'txt.bz2');
      `dpkg --get-selections | grep -v deinstall | bzip2 --best > {$filename}`;
      $parent->trigger('file', array('filename' => $filename));
      $parent->log(" ..done", 1);
    }
    else
    {
      $parent->log("unknown packaging mode: {$config['packages']}", 3);
    }
  }
}