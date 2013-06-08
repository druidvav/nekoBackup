<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\BackupLogger;
use nekoBackup\BasicDriver\Action;

class SystemAction extends Action
{
  public function execute($config, $period)
  {
    if ($config['period'] != $period) {
      BackupLogger::append("skipping ({$config['period']})", 1);
      return;
    }

    if($config['packages'] != 'Debian') {
      BackupLogger::append("unknown packaging mode: {$config['packages']}", 3);
      return;
    }

    BackupLogger::append("reading packages for Debian..", 1);

    $filename = $this->prepareFilename('system-packages', 'txt.bz2');
    exec(sprintf("dpkg --get-selections | grep -v deinstall | bzip2 --best > %s", escapeshellarg($filename)));
    $this->driver->reportFileReady($filename);

    BackupLogger::append(" ..done", 1);
  }
}