<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\BackupLogger;

use nekoBackup\BasicDriver\Action;

class SubdirectoryAction extends DirectoryAction
{
  public function execute($config, $period)
  {
    foreach($config as $pkg => $pkg_config) {
      BackupLogger::indent($pkg);

      if(empty($pkg_config['period']) || $pkg_config['period'] == $period) {
        $pkg_config['pkg'] = $pkg;
        $this->executeSection($pkg_config, $period);
      } else {
        BackupLogger::append("skipping ({$pkg_config['period']})", 1);
      }

      BackupLogger::back();
    }
  }

  protected function executeSection($config, $period)
  {
    $base = explode('|', $config['base']);

    foreach($this->getDirectoryList($base[0]) as $dir) {
      if(!empty($config['exclude']) && in_array($dir, $config['exclude'])) {
        continue;
      }

      BackupLogger::indent($dir);

      $period_1 = @$config['period_override'][$dir] ? $config['period_override'][$dir] : @$config['period_default'];
      if(!empty($base[1])) {
        $this->executeSubsection($base, $config, $period, $dir, $period_1);
      } elseif(!empty($period_1) && $period_1 != $period) {
        BackupLogger::append('skipping (' . $period_1 . ')', 1);
      } else {
        $this->executeSingle(array(
          'code' => $config['pkg'] . '-' . $dir,
          'base' => $base[0] . '/' . $dir,
          'exclude' => @$config['subexclude'], // TODO subexclude_override
        ));
      }

      BackupLogger::back();
    }
  }

  protected function executeSubsection($base, $config, $period, $dir, $period_1)
  {
    foreach ($this->getDirectoryList($base[0] . '/' . $dir . $base[1]) as $subdir) {
      if(!empty($config['exclude']) && in_array($dir . '_' . $subdir, $config['exclude'])) {
        continue;
      }

      BackupLogger::indent($subdir);

      $override = @$config['period_override']["{$dir}_{$subdir}"] ? $config['period_override']["{$dir}_{$subdir}"] : $period_1;
      if(!empty($override) && $override != $period) {
        BackupLogger::append('skipping (' . $override . ')', 1);
      } else {
        $this->executeSingle(array(
          'code' => $config['pkg'] . '-' . $dir . '-' . $subdir,
          'base' => $base[0] . '/' . $dir . $base[1] . '/' . $subdir,
          'exclude' => !empty($config['subexclude_override'][$dir . '_' . $subdir])
            ? $config['subexclude_override'][$dir . '_' . $subdir]
            : @$config['subexclude'], // TODO Subexclude override for $dir
        ));
      }

      BackupLogger::back();
    }
  }

  protected function getDirectoryList($base)
  {
    $list = array();
    if(is_dir($base)) foreach(scandir($base) as $dir) {
      if($dir == '.' || $dir == '..') {
        continue; // Если системная директория или директория исключена - пропускаем
      }
      $list[] = $dir;
    }
    return $list;
  }

  protected function executeSingle($config)
  {
    $this->archiveDirectory($this->prepareFilename($config['code'], 'tar.gz'), $config);
  }
}