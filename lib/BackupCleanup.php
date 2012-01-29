<?php
class BackupCleanup
{
  public static function execute(Backup &$parent, $config)
  {
    $dirs = `ls {$parent->config['storage']}`;
    foreach(explode("\n", $dirs) as $dir)
    {
      if(empty($dir)) continue;

      if(!self::checkDateDirectory($parent, $dir))
      {
        BackupLogger::append("{$dir} expired");

        if(!$parent->trigger('cleanup.before', array('directory' => $dir)))
        { // Error while deleting
          continue;
        }

        `rm -f {$parent->config['storage']}{$dir}/*`;
        `rmdir {$parent->config['storage']}{$dir}/`;

        $parent->trigger('cleanup.after', array('directory' => $dir));

        BackupLogger::append($dir . ' deleted');
      }
      else
      {
        BackupLogger::append("{$dir} actual");
      }
    }
    $parent->trigger('cleanup');
  }

  public static function checkDateDirectory(Backup &$parent, $dir)
  {
    $date = strtotime($dir);
    $period = $parent->getDatePeriod($date);

    if(time() > strtotime('+' . $parent->config['cleanup'][$period], $date))
    {
      return false;
    }
    else
    {
      return true;
    }
  }
}