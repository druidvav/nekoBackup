<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\Backup;
use nekoBackup\BasicDriver\Action;

class CleanupAction extends Action
{
  protected $action = 'cleanup';

  public function execute($period)
  {
    $config = $this->getGlobalConfig();

    $dirs = `ls {$config['storage']}`;
    foreach(explode("\n", $dirs) as $dir) {
      if(empty($dir)) continue;

      $this->indent($dir);

      if(!$this->checkDateDirectory($dir)) {
        $this->write("expired..");
        exec("rm -f {$config['storage']}{$dir}/*");
        exec("rmdir {$config['storage']}{$dir}/");
        $this->write(' ..deleted');
      } else {
        $this->write("actual");
      }

      $this->back();
    }
  }

  public function checkDateDirectory($dir)
  {
    $date = strtotime($dir);
    $period = Backup::getDatePeriod($date);
    $config = $this->getActionConfig();
    return !(time() > strtotime('+' . $config[$period], $date));
  }
}