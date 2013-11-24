<?php
namespace nekoBackup;

use Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class App
{
  protected $config;
  protected $archives = array();

  private $classMap = array(
    'directory' => 'Builder\\Directory',
    'mysql' => 'Builder\\MySQL',
    'postgres' => 'Builder\\Postgres',
  );

  public function __construct()
  {
    if(!file_exists(CONFIG_FILE)) {
      throw new Exception('Config file does not exist.');
    }

    $this->config = new Config();

    if(!is_dir($this->config->get('storage'))) {
      throw new Exception('Storage directory does not exist.');
    }

    if(!is_dir($this->config->get('metadata'))) {
      throw new Exception('Metadata directory does not exist.');
    }
  }

  public function archive()
  {
    $this->buildQueue();
    $this->runQueue();
  }

  protected function buildQueue()
  {
    Logger::append('Building backup queue...');

    foreach($this->config->get('sections') as $title => $section) {
      Logger::indent($title);
      Logger::append('Scanning...');
      $className = $this->classMap[$section['type']];
      $builder = new $className($this->config, $title, $section);
      /* @var $builder Builder\AbstractBuilder */
      foreach($builder->getArchives() as $archive) {
        $this->archives[] = $archive;
      }
      Logger::append('Complete!');
      Logger::back();
    }

    Logger::append('Backup queue ready.');
  }

  protected function runQueue()
  {
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
        $action = new Upload\AmazonS3($this->config);
        if($action->upload($dir->getRealPath(), 3)) {
          file_put_contents($dir->getRealPath() . '.uploaded', date('r'));
        }

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
    $action = new Upload\AmazonS3($this->config);
    $action->cleanup();
    Logger::append('Cleanup finished');
  }

  public function checkPid($action)
  {
    $pidfile = TMP_PATH . $action . '.pid';
    if(!file_exists($pidfile)) {
      $pid = getmypid();
      file_put_contents($pidfile, $pid);
    } else {
      $pid = intval(file_get_contents($pidfile));
      if(file_exists("/proc/$pid")) {
        throw new \Exception('This type of process is already running');
      } else {
        $pid = getmypid();
        file_put_contents($pidfile, $pid);
      }
    }
  }
}