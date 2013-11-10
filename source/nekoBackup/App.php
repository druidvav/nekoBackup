<?php
namespace nekoBackup;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class App
{
  protected $config;
  protected $archives = array();

  public function __construct()
  {
    $this->config = new Config();
  }

  protected function buildQueue()
  {
    Logger::append('Building backup queue...');

    foreach($this->config->get('sections') as $title => $section) {
      Logger::indent($title);
      Logger::append('Scanning...');
      if($section['type'] == 'directory') {
        $builder = new Builder\Directory($this->config, $title, $section);
      } elseif ($section['type'] == 'mysql') {
        $builder = new Builder\MySQL($this->config, $title, $section);
      } elseif ($section['type'] == 'postgres') {
        $builder = new Builder\Postgres($this->config, $title, $section);
      } else {
        continue;
      }
      foreach($builder->getArchives() as $archive) {
        $this->archives[] = $archive;
      }
      Logger::append('Complete!');
      Logger::back();
    }

    Logger::append('Backup queue ready.');
  }

  public function archive()
  {
    $this->buildQueue();

    Logger::append('Starting backup...');
    foreach($this->archives as $archive) {
      /* @var Archive\AbstractArchive $archive */
      Logger::indent($archive->getTitle());
      Logger::append('Backuping...');
      try {
        $archive->create();
        $filename = $archive->getArchiveFilename();
        Logger::append('Archive ready: ' . $filename);
      } catch(Archive\Exception\ArchiveExists $e) {
        $filename = $e->getArchiveFilename();
        Logger::append('Archive exists: ' . $filename);
      }
      Logger::append('Finished!');
      Logger::back();
    }
    Logger::append('Backup finished!');
  }

  public function cleanup()
  {
    Logger::append('Starting cleanup...');

    Logger::indent('cleanup-files');
    $finder = new Finder();
    $finder->files()->in($this->config->get('storage'))->sortByName();
    foreach ($finder as $file) {
      /* @var SplFileInfo $file */
      Logger::indent($file->getBasename());
      if($this->config->checkIfArchiveExpired($file->getBasename())) {
        Logger::append('expired, removing...');
        unlink($file->getRealpath());
        Logger::append('removed!');
      } else {
        Logger::append('actual');
      }
      Logger::back();
    }
    Logger::back();

    Logger::indent('cleanup-dirs');
    $finder = new Finder();
    $finder->directories()->in($this->config->get('storage'))->sortByName();
    foreach ($finder as $dir) {
      /* @var SplFileInfo $dir */
      Logger::indent($dir->getBasename());
      $filesFinder = new Finder();
      if($filesFinder->files()->in($dir->getRealpath())->count() == 0) {
        Logger::append('empty, removing');
        rmdir($dir->getRealpath());
        Logger::append('removed!');
      } else {
        Logger::append('not empty');
      }
      Logger::back();
    }
    Logger::back();

    Logger::append('Cleanup finished!');
  }

  public function uploadAmazonS3()
  {
    if(!$this->config->get('amazonS3')) {
      Logger::append('Amazon S3 uploader is not configured.');
      return;
    }

    $nothingFoundFlag = false;

    while(!$nothingFoundFlag) {
      $nothingFoundFlag = true;
      Logger::append('Searching for files to upload...');

      $finder = new Finder();
      $finder->files()->in($this->config->get('storage'))->sortByName();
      foreach ($finder as $dir) {
        /* @var SplFileInfo $dir */
        $basename = $dir->getBasename();

        if(preg_match('#\.(uploaded|tmp)$#', $basename)) {
          // This is semaphore file, so we just ignore it
          continue;
        }

        if(file_exists($dir->getRealPath() . '.uploaded')) {
          // This file is already uploaded
          continue;
        }

        Logger::indent($dir->getBasename());

        $nothingFoundFlag = false;
        $action = new S3Driver\UploadAction($this->config);
        $action->execute($dir->getRealPath(), 3);
        file_put_contents($dir->getRealPath() . '.uploaded', date('r'));

        Logger::back();
      }
      if($nothingFoundFlag) {
        Logger::append('Nothing to upload.');
      } else {
        Logger::append('Upload process finished.');
      }
    }
  }

  public function cleanupAmazonS3()
  {
    if(!$this->config->get('amazonS3')) {
      Logger::append('Amazon S3 uploader is not configured.');
      return;
    }

    Logger::append('Cleaning up...');
    $action = new S3Driver\CleanupAction($this->config);
    $action->execute();
    Logger::append('Cleanup finished');
  }
}