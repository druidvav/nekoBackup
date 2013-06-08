<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\BasicDriver\Action;

class SystemAction extends Action
{
  public function execute($period)
  {
    $config = $this->getActionConfig();

    if ($config['period'] != $period) {
      $this->write("skipping ({$config['period']})");
      return;
    }

    if($config['packages'] != 'Debian') {
      $this->write("unknown packaging mode: {$config['packages']}");
      return;
    }

    $this->write("reading packages for Debian..");

    $filename = $this->prepareFilename('system-packages', 'txt.bz2');
    exec(sprintf("dpkg --get-selections | grep -v deinstall | bzip2 --best > %s", escapeshellarg($filename)));
    $this->reportFileReady($filename);

    $this->write(" ..done");
  }
}