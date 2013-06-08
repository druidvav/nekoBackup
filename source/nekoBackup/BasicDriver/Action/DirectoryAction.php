<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\BackupLogger;

use nekoBackup\BasicDriver\Action;
use nekoBackup\Event\FileReady as FileReadyEvent;

class DirectoryAction extends Action
{
  public function execute($config, $period)
  {
    foreach($config as $pkg => $pkg_config) {
      BackupLogger::indent($pkg);

      if($pkg_config['period'] == $period) {
        $this->archiveDirectory($this->prepareFilename($pkg, 'tar.gz'), $pkg_config);
      } else {
        BackupLogger::append("skipping ({$pkg_config['period']})", 1);
      }

      BackupLogger::back();
    }
  }

  protected function archiveDirectory($file, $config)
  {
    global $dispatcher;

    $included = array();
    $excluded = array();

    if(!empty($config['base'])) {
      $included[] = $config['base'];
    }

    if(!empty($config['include'])) foreach($config['include'] as $dir) {
      $included[] = $dir{0} != '/' ? @$config['base'] . '/' . $dir : $dir;
    }

    if(!empty($config['exclude'])) foreach($config['exclude'] as $dir) {
      $excluded[] = $dir{0} != '/' ? @$config['base'] . '/' . $dir : $dir;
    }

    BackupLogger::append("archiving..", 1);

    $this->archive($file, $included, $excluded);
    $dispatcher->dispatch('nekobackup.file-ready', new FileReadyEvent($file));

    BackupLogger::append(" ..done", 1);
  }

  protected function archive($archive, $include, $exclude = array())
  {
    $cl_include = implode(' ', $include);

    // If excluded dir is included -- we must remove it from exclusion
    foreach($exclude as &$dir) if(in_array($dir, $include)) $dir = '';

    $cl_exclude = '';
    foreach($exclude as $dir) {
      if(!empty($dir)) $cl_exclude .= ' --exclude="' . $dir . '"';
    }

    return `tar {$cl_exclude} -czpf {$archive} {$cl_include} 2>&1`;
  }
}