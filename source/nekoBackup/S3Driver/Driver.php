<?php
namespace nekoBackup\S3Driver;

use Aws\S3\S3Client;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\Common\Exception\MultipartUploadException;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Symfony\Component\Yaml\Yaml;
use nekoBackup\Backup;
use nekoBackup\BackupLogger;

class Driver
{
  protected $config;

  /**
   * @var S3Client
   */
  protected $client;

  public function __construct($config_path, EventDispatcher $dispatcher)
  {
    if(!is_file($config_path)) {
      BackupLogger::append('File "' . $config_path . '" not found');
      die(1);
    }

    $this->config = Yaml::parse($config_path);
    $this->config['directory'] = $this->config['directory'] . "/";

    $this->client = S3Client::factory(array(
      'key'    => $this->config['access_key'],
      'secret' => $this->config['secret_key']
    ));

    $dispatcher->addSubscriber(new EventSubscriber($this));
  }

  public function uploadFile($filename, $retries = 3)
  {
    $storage_dir = Backup::get()->config['storage'];
    $remote_file = $this->config['directory'] . str_replace($storage_dir, '', $filename);

    $uploader = UploadBuilder::newInstance()
      ->setClient($this->client)
      ->setSource($filename)
      ->setBucket($this->config['bucket'])
      ->setKey($remote_file)
      ->setConcurrency(4)
      ->setMinPartSize(50*1024*1024) // 50 Mb
      ->build();

    BackupLogger::append('uploading file..', 1);

    try {
      $uploader->upload();
      BackupLogger::append('complete!', 1);
      BackupLogger::append('removing local file..', 1);
      unlink($filename);
    } catch (MultipartUploadException $e) {
      if($retries > 0) {
        BackupLogger::append('failed: ' . $e->getMessage(), 1);
        BackupLogger::append('retrying...', 1);
        return $this->uploadFile($filename, $retries - 1);
      } else {
        $uploader->abort();
        BackupLogger::append('failed: ' . $e->getMessage(), 1);
        exit;
      }
    }

    return true;
  }

  public function cleanup()
  {
    $action = new Action\CleanupAction($this);
    $action->execute();
  }
}
