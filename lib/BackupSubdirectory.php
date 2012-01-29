<?php
class BackupSubdirectory
{
  public static function execute(Backup &$parent, $config)
  {
    foreach($config as $pkg => $pkg_config)
    {
      $parent->logIndent($pkg);

      if($pkg_config['period'] == $parent->period)
      {
        $base = explode('|', $pkg_config['base']);

        foreach(self::getDirectoryList($base[0]) as $dir)
        {
          if(!empty($pkg_config['exclude']) && in_array($dir, $pkg_config['exclude']))
          {
            continue;
          }

          $parent->logIndent($dir);

          $override = @$pkg_config['period_override'][$dir];
          if(!empty($override) && $override != $parent->period)
          {
            $parent->log('skipping (' . $override . ')', 1);
          }
          elseif(!empty($base[1]))
          {
            foreach (self::getDirectoryList($base[0] . '/' . $dir . $base[1]) as $subdir)
            {
              if(!empty($pkg_config['exclude']) && in_array($dir . '_' . $subdir, $pkg_config['exclude']))
              {
                continue;
              }

              $parent->logIndent($subdir);

              $override = @$pkg_config['period_override'][$dir . '_' . $subdir];
              if(!empty($override) && $override != $parent->period)
              {
                $parent->log('skipping (' . $override . ')', 1);
              }
              else
              {
                self::executeSingle($parent, array(
                  'code' => $pkg . '-' . $dir . '-' . $subdir,
                  'base' => $base[0] . '/' . $dir . $base[1] . '/' . $subdir,
                  'exclude' => !empty($pkg_config['subexclude_override'][$dir . '_' . $subdir])
                                  ? $pkg_config['subexclude_override'][$dir . '_' . $subdir]
                                  : @$pkg_config['subexclude'], // TODO Subexclude override for $dir
                ));
              }

              $parent->logIndentBack();
            }
          }
          else
          {
            self::executeSingle($parent, array(
              'code' => $pkg . '-' . $dir,
              'base' => $base[0] . '/' . $dir,
              'exclude' => @$pkg_config['subexclude'], // TODO subexclude_override
            ));
          }

          $parent->logIndentBack();
        }
      }
      else
      {
        $parent->log("skipping ({$pkg_config['period']})", 1);
      }

      $parent->logIndentBack();
    }
  }

  protected static function getDirectoryList($base)
  {
    $list = array();
    if(is_dir($base)) foreach(scandir($base) as $dir)
    {
      if($dir == '.' || $dir == '..')
      { // Если системная директория или директория исключена - пропускаем
        continue;
      }

      $list[] = $dir;
    }
    return $list;
  }

  protected static function executeSingle(Backup &$parent, $config)
  {
    BackupDirectory::executeSingle($parent, $parent->prepareFilename($config['code'], 'tar.bz2'), $config);
  }
}