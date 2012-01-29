<?php
class BackupCleanup
{
  public static function execute($config, $period)
  {
    $storage_dir = Backup::get()->config['storage'];

    $dirs = `ls {$storage_dir}`;
    foreach(explode("\n", $dirs) as $dir)
    {
      if(empty($dir)) continue;

      if(!self::checkDateDirectory($dir))
      {
        BackupLogger::append("{$dir} expired");

        if(!BackupEvents::trigger('cleanup.before', array('directory' => $dir)))
        { // Error while deleting
          continue;
        }

        `rm -f {$storage_dir}{$dir}/*`;
        `rmdir {$storage_dir}{$dir}/`;

        BackupEvents::trigger('cleanup.after', array('directory' => $dir));

        BackupLogger::append($dir . ' deleted');
      }
      else
      {
        BackupLogger::append("{$dir} actual");
      }
    }
    BackupEvents::trigger('cleanup');
  }

  public static function checkDateDirectory($dir)
  {
    $date = strtotime($dir);
    $period = Backup::getDatePeriod($date);
    $config = Backup::get()->config['cleanup'];
    return !(time() > strtotime('+' . $config[$period], $date));
  }
}