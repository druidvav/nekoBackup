<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\BasicDriver\Action;
use Symfony\Component\Finder\Finder;

class SubdirectoryAction extends DirectoryAction
{
  public function execute($period)
  {
    foreach($this->getActionConfig() as $section => $sectionConfig) {
      $this->indent($section);

      if(empty($sectionConfig['period']) || $sectionConfig['period'] == $period) {
        $sectionConfig['section'] = $section;
        $this->archiveSection($sectionConfig, $period);
      } else {
        $this->write("skipping ({$sectionConfig['period']})", 1);
      }

      $this->back();
    }
  }

  protected function archiveSection($sectionConfig, $period)
  {
    $finder = new Finder();
    $finder->directories()->in($sectionConfig['base'])->depth(0);
    foreach ($finder as $file) {
      $directory = $file->getRealpath();

      preg_match('#^' . str_replace('\\*', '(.*?)', preg_quote($sectionConfig['base'] . '/*')) . '$#', $directory, $match);
      unset($match[0]);

      $sectionId = '';
      $checkIds = array();
      foreach($match as $subdir) {
        $sectionId .= ($sectionId == '' ? '' : '_') . $subdir;
        $checkIds[] = $sectionId;
      }

      if(!empty($sectionConfig['exclude'])) {
        $excluded = false;
        foreach($checkIds as $checkId) {
          if(in_array($checkId, $sectionConfig['exclude'])) {
            $excluded = true;
            break;
          }
        }
        if($excluded) {
          continue;
        }
      }

      $this->indent(implode(' > ', $match));

      $sectionPeriod = @$sectionConfig['period_default'];
      foreach($checkIds as $checkId) {
        if(!empty($sectionConfig['period_override'][$checkId])) {
          $sectionPeriod = $sectionConfig['period_override'][$checkId];
        }
      }

      if(!empty($sectionPeriod) && $sectionPeriod != $period) {
        $this->write('skipping (' . $sectionPeriod . ')', 1);
      } else {
        parent::archiveSection(
          $this->prepareFilename($sectionConfig['section'] . '-' . implode('-', $match), 'tar.gz'),
          $directory, array(), @$sectionConfig['subexclude']
        );
      }

      $this->back();
    }
  }

//    'exclude' => !empty($config['subexclude_override'][$dir . '_' . $subdir])
//      ? $config['subexclude_override'][$dir . '_' . $subdir]
//      : @$config['subexclude'], // TODO Subexclude override for $dir
}