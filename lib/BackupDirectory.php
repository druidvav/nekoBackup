<?php
class BackupDirectory
{
  public static function execute(Backup &$parent, $config)
  {
    foreach($config as $pkg => $pkg_config)
    {
      BackupLogger::indent($pkg);

      if($pkg_config['period'] == $parent->period)
      {
        BackupDirectory::executeSingle($parent, $parent->prepareFilename($pkg, 'tar.bz2'), $pkg_config);
      }
      else
      {
        BackupLogger::append("skipping ({$pkg_config['period']})", 1);
      }

      BackupLogger::back();
    }
  }

  public static function executeSingle(Backup &$parent, $file, $config)
  {
    $included = array();
    $excluded = array();

    if(!empty($config['base']))
    {
      $included[] = $config['base'];
    }

    if(!empty($config['include'])) foreach($config['include'] as $dir)
    {
      $included[] = $dir{0} != '/' ? @$config['base'] . '/' . $dir : $dir;
    }

    if(!empty($config['exclude'])) foreach($config['exclude'] as $dir)
    {
      $excluded[] = $dir{0} != '/' ? @$config['base'] . '/' . $dir : $dir;
    }

    BackupLogger::append("archiving..", 1);
    self::archive($file, $included, $excluded);
    $parent->trigger('file', array('filename' => $file));
    BackupLogger::append(" ..done", 1);
  }

  protected static function archive($archive, $include, $exclude = array())
  {
    $cl_include = implode(' ', $include);

    // If excluded dir is included -- we must remove it from exclusion
    foreach($exclude as &$dir) if(in_array($dir, $include)) $dir = '';

    $cl_exclude = '';
    foreach($exclude as $dir)
    {
      if(!empty($dir)) $cl_exclude .= ' --exclude="' . $dir . '"';
    }

    return `tar {$cl_exclude} -cjpf {$archive} {$cl_include} 2>&1`;
    //echo "tar {$cl_exclude} -cjpf {$archive} {$cl_include} 2>&1" . "\n";
  }
}