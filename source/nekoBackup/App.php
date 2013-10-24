<?php
namespace nekoBackup;

use nekoBackup\Event\Action as ActionEvent;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcher;

class App
{
  protected $config;
  protected $dispatcher;
  protected $archives = array();

  public function __construct()
  {
    $this->config = new Config();
    $this->dispatcher = new EventDispatcher();

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

    if($this->config->get('amazonS3')) {
      Logger::append('Using [Amazon S3] extension.');
      $this->dispatcher->addSubscriber(new S3Driver\EventSubscriber($this->config));
    }
  }

  public function archive()
  {
    Logger::append('Starting backup...');
    foreach($this->archives as $archive) {
      /* @var Archive\AbstractArchive $archive */
      Logger::indent($archive->getTitle());
      Logger::append('Backuping...');
      $archive->create();
      $filename = $archive->getArchiveFilename();
      Logger::append('Got file: ' . $filename);
      $this->dispatcher->dispatch('nekobackup.file-ready', new Event\FileReady($filename));
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

    $this->dispatcher->dispatch('nekobackup.action', new Event\Action('cleanup'));

    Logger::append('Cleanup finished!');
  }
}