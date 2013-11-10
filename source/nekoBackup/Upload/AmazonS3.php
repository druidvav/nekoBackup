<?php
namespace nekoBackup\Upload;

use Aws\S3\S3Client;
use nekoBackup\Logger;
use nekoBackup\Config;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\Common\Exception\MultipartUploadException;

class AmazonS3
{
  /**
   * @var S3Client
   */
  private $client;

  /**
   * @var Config
   */
  protected $config;

  public function __construct(Config $config)
  {
    $this->config = $config;
  }

  protected function client()
  {
    if(empty($this->client)) {
      $config = $this->getS3Config();
      $this->client = S3Client::factory(array(
        'key'    => $config['access_key'],
        'secret' => $config['secret_key']
      ));
    }
    return $this->client;
  }

  /**
   * @param $filename
   * @return UploadBuilder
   */
  protected function getUploader($filename)
  {
    return UploadBuilder::newInstance()
      ->setClient($this->client())
      ->setSource($filename);
  }

  protected function getS3Config()
  {
    $config = $this->config->get('amazonS3');
    $config['directory'] = $config['directory'] . "/";
    return $config;
  }

  public function upload($filename, $retries = 3)
  {
    $config = $this->getS3Config();

    $remote_file = str_replace($this->config->get('storage'), $config['directory'], $filename);

    $uploader = $this->getUploader($filename)
      ->setBucket($config['bucket'])
      ->setKey($remote_file)
      ->setConcurrency(4)
      ->setMinPartSize(50*1024*1024) // 50 Mb
      ->build();

    Logger::append('uploading file..');

    try {
      $uploader->upload();
      Logger::append('complete!');
      if($config['deleteAfterUpload']) {
        Logger::append('removing local file..');
        unlink($filename);
      }
    } catch (MultipartUploadException $e) {
      if($retries > 0) {
        Logger::append('failed: ' . $e->getMessage());
        Logger::append('retrying...', 1);
        $this->upload($filename, $retries - 1);
        return;
      } else {
        $uploader->abort();
        Logger::append('failed: ' . $e->getMessage());
      }
    }
  }

  public function cleanup()
  {
    $config = $this->getS3Config();

    $iterator = $this->client()->getIterator('ListObjects', array(
      'Bucket' => $config['bucket'],
      'Prefix' => $config['directory']
    ));

    Logger::indent('cleanup-s3-files');
    foreach($iterator as $object) {
      Logger::indent(basename($object['Key']));
      if($this->config->checkIfArchiveExpired($object['Key'])) {
        Logger::append('expired, removing');
        $this->client()->deleteObject(array(
          'Bucket' => $config['bucket'],
          'Key' => $object['Key']
        ));
        Logger::append('removed!');
      } else {
        Logger::append('actual');
      }
      Logger::back();
    }
    Logger::back();

    // FIXME check if we need to delete empty directories
  }
}