<?php
namespace nekoBackup\Storage;

use nekoBackup\Logger;
use Symfony\Component\Finder\SplFileInfo;

class Basic extends AbstractStorage
{
  public function cleanup()
  {
    $this->cleanupFiles();
    $this->cleanpDirectories();
  }

  protected function cleanupFiles()
  {
    foreach($this->getStorageFinder()->files() as $file) {
      /* @var SplFileInfo $file */
      if($this->config->checkIfArchiveExpired($file->getBasename())) {
        Logger::append($file->getBasename() . ' expired.');
        unlink($file->getRealpath());
      }
    }
  }

  protected function cleanpDirectories()
  {
    foreach($this->getStorageFinder()->directories() as $dir) {
      /* @var SplFileInfo $dir */
      if($this->getFinder()->files()->in($dir->getRealpath())->count() == 0) {
        Logger::append($dir->getBasename() . ' is empty.');
        rmdir($dir->getRealpath());
      }
    }
  }
}