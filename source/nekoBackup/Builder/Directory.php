<?php
namespace nekoBackup\Builder;

use nekoBackup\Archive;

class Directory extends AbstractBuilder
{
  public function build()
  {
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
    if(is_dir($expression)) {
      $directories = array($expression);
    } else {
      $directories = glob($expression, GLOB_ONLYDIR);
      sort($directories);
    }

    foreach ($directories as $path) {
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
          $archive->setIncrementalBase();
          $archive->setExpiresIn($cleanup['base']);
        }
      } else {
        unset($this->archives[$id]);
      }
    }
  }
}