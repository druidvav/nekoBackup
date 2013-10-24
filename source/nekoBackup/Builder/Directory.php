<?php
namespace nekoBackup\Builder;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use nekoBackup\Config;
use nekoBackup\Archive;

class Directory
{
  protected $title;
  protected $config;
  protected $section;
  protected $archives = array();

  public function __construct(Config $config, $title, $section)
  {
    $this->title = $title;
    $this->config = $config;
    $this->section = $section;
    $this->prepareArchives($this->section['include']);
    if(!empty($this->section['exclude'])) {
      $this->handleExcludes($this->section['exclude']);
    }
    $this->handleSchedule($this->section['schedule'], $this->section['cleanup']);
  }

  public function prepareArchives($paths)
  {
    if(strpos($paths[0], '*') !== false) {
      $this->prepareArchivesByExpression($paths[0]);
    } else {
      $archive = new Archive\Directory($this->config, $this->title);
      $archive->setInclude($paths);
      $this->archives[] = $archive;
    }
    return $this;
  }

  private function prepareArchivesByExpression($expression)
  {
    if(substr($expression, -2) != '/*') {
      throw new \Exception('Path expression is not supported');
    }

    $finder = new Finder();
    $finder->directories()->in(substr($expression, 0, -2))->depth('== 0')->sortByName();
    foreach ($finder as $file) {
      /* @var SplFileInfo $file */
      $path = $file->getRealpath();
      $title = $this->generateTitleForExpressionArchive($path, $expression);
      $archive = new Archive\Directory($this->config, $title);
      $archive->setInclude($path);
      $this->archives[] = $archive;
    }
  }

  protected function generateTitleForExpressionArchive($path, $exp)
  {
    preg_match('#^' . str_replace('\\*', '(.*?)', preg_quote($exp)) . '$#', $path, $match);
    unset($match[0]);
    return $this->title . '-' . implode('-', $match);
  }

  protected function handleExcludes($paths)
  {
    foreach($paths as $path) {
      foreach($this->archives as $id => &$archive) {
        /* @var Archive\Directory $archive */
        $archive->exclude($path);
        if($archive->isEmpty()) {
          unset($this->archives[$id]);
        }
      }
    }
  }

  protected function handleSchedule($schedule, $cleanup)
  {
    if(!is_array($schedule)) {
      throw new \Exception('Unsupported schedule format.');
    }

    foreach($this->archives as $id => &$archive) {
      /* @var Archive\Directory $archive */
      if($this->config->checkScheduleMatch($schedule['base'])) {
        $archive->setIncrementalBase();
        $archive->setExpiresIn($cleanup['base']);
      } elseif($this->config->checkScheduleMatch($schedule['incremental'])) {
        $baseDate = $this->config->getLastMatchedDate($schedule['base']);
        if($archive->setIncremental($baseDate)) {
          $archive->setExpiresIn($cleanup['incremental']);
        } else {
          $archive->setIncrementalBase($baseDate);
          $archive->setExpiresIn($cleanup['base']);
        }
      } else {
        unset($this->archives[$id]); // FIXME
      }
    }
  }

  public function getArchives()
  {
    return $this->archives;
  }
}