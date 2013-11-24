<?php
namespace nekoBackup;

use Exception;

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
      Logger::append("Scanning $title...");
      $className = $this->classMap[$section['type']];
      $builder = new $className($this->config, $title, $section);
      /* @var $builder Builder\AbstractBuilder */
      foreach($builder->getArchives() as $archive) {
        $this->archives[] = $archive;
      }
      Logger::append('Complete!');
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
    $action = new Storage\Basic($this->config);
    $action->cleanup();
    Logger::append('Cleanup finished!');
  }

  public function uploadAmazonS3()
  {
    if(!$this->config->get('amazonS3')) {
      throw new Exception('Amazon S3 uploader is not configured.');
    }

    $action = new Storage\AmazonS3($this->config);

    $nothingFoundFlag = false;
    while(!$nothingFoundFlag) {
      Logger::append('Starting upload to S3...');
      $nothingFoundFlag = $action->upload() == 0;
      Logger::append($nothingFoundFlag ? 'Nothing to upload.' : 'Upload process finished.');
    }
  }

  public function cleanupAmazonS3()
  {
    if(!$this->config->get('amazonS3')) {
      throw new Exception('Amazon S3 uploader is not configured.');
    }

    Logger::append('Cleaning up...');
    $action = new Storage\AmazonS3($this->config);
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