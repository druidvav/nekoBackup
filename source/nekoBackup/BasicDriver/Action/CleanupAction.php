<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\Backup;
use nekoBackup\BackupLogger;

use nekoBackup\BasicDriver\Action;

class CleanupAction extends Action
{
  public function execute($config, $period)
  {
    $storage_dir = $this->config['storage'];

    $dirs = `ls {$storage_dir}`;
    foreach(explode("\n", $dirs) as $dir) {
      if(empty($dir)) continue;

      if(!$this->checkDateDirectory($dir)) {
        BackupLogger::append("{$dir} expired");

        exec("rm -f {$storage_dir}{$dir}/*");
        exec("rmdir {$storage_dir}{$dir}/");

        BackupLogger::append($dir . ' deleted');
      } else {
        BackupLogger::append("{$dir} actual");
      }
    }
  }

  public function checkDateDirectory($dir)
  {
    $date = strtotime($dir);
    $period = Backup::getDatePeriod($date);
    $config = Backup::get()->config['cleanup'];
    return !(time() > strtotime('+' . $config[$period], $date));
  }
}