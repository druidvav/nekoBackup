<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\BasicDriver\Action;
use nekoBackup\Event\FileReady as FileReadyEvent;

class DirectoryAction extends Action
{
  protected $action = 'directory';

  public function execute($period)
  {
    foreach($this->getActionConfig() as $section => $sectionConfig) {
      $this->indent($section);

      if($sectionConfig['period'] == $period) {
        $this->archiveSection(
          $this->prepareFilename($section, 'tar.gz'),
          @$sectionConfig['base'], @$sectionConfig['include'], @$sectionConfig['exclude']
        );
      } else {
        $this->write("skipping ({$sectionConfig['period']})");
      }

      $this->back();
    }
  }

  protected function archiveSection($archive, $directory, $include, $exclude)
  {
    $included = array();
    $excluded = array();

    if(!empty($directory)) {
      $included[] = $directory;
    }

    if(!empty($include)) foreach($include as $includeDir) {
      $included[] = $includeDir{0} != '/' ? $directory . '/' . $includeDir : $includeDir;
    }

    if(!empty($exclude)) foreach($exclude as $excludeDir) {
      $excluded[] = $excludeDir{0} != '/' ? $directory . '/' . $excludeDir : $excludeDir;
    }

    $this->write("archiving..");
    $this->archive($archive, $included, $excluded);
    $this->dispatcher()->dispatch('nekobackup.file-ready', new FileReadyEvent($archive));
    $this->write(" ..done");
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

    return `nice -n 19 tar {$cl_exclude} -czpf {$archive} {$cl_include} 2>&1`;
  }
}