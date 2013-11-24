<?php
namespace nekoBackup\Storage;

use Aws\S3\S3Client;
use nekoBackup\Logger;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\Common\Exception\MultipartUploadException;
use Symfony\Component\Finder\SplFileInfo;

class AmazonS3 extends AbstractStorage
{
  /**
   * @var S3Client
   */
  private $client;

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

  public function upload()
  {
    $counter = 0;

    foreach($this->getStorageFinder()->files() as $dir) {
      /* @var SplFileInfo $dir */
      if(preg_match('#\.(uploaded|tmp)$#', $dir->getBasename())) {
        // This is semaphore file, so we just ignore it
        continue;
      }

      if(file_exists($dir->getRealPath() . '.uploaded')) {
        // This file is already uploaded
        continue;
      }

      Logger::indent($dir->getBasename());
      Logger::append('uploading file..');
      if($this->uploadFile($dir->getRealPath(), 3)) {
        file_put_contents($dir->getRealPath() . '.uploaded', date('r'));
      }
      Logger::back();

      $counter++;
    }

    return $counter;
  }

  public function uploadFile($filename, $retries = 3)
  {
    $config = $this->getS3Config();

    $remote_file = str_replace($this->config->get('storage'), $config['directory'], $filename);

    $uploader = $this->getUploader($filename)
      ->setBucket($config['bucket'])
      ->setKey($remote_file)
      ->setMinPartSize(200 * 1024 * 1024)
      ->setConcurrency(10)
      ->build();

    $uploader->getEventDispatcher()->addListener($uploader::AFTER_PART_UPLOAD, function ($eventData) {
      $eventDataSource = $eventData['source'];
      /* @var $eventDataSource \Guzzle\Http\EntityBody */
      $contentLength = $eventDataSource->getContentLength();
      $totalParts = (int) ceil($contentLength / $eventData['part_size']);
      $currentPart = count($eventData['state']);
      $percent = intval(1000 * $currentPart / $totalParts) / 10;
      if ($currentPart % 5 == 0) {
        Logger::append($percent . '% of ' . intval($contentLength / (1024 * 1024)) . 'M uploaded');
      }
    });

    try {
      $uploader->upload();
      Logger::append('complete!');
      if($config['deleteAfterUpload']) {
        Logger::append('removing local file..');
        unlink($filename);
      }
      return true;
    } catch (MultipartUploadException $e) {
      Logger::append('failed: ' . $e->getMessage());
      if($retries > 0) {
        Logger::append('retrying...', 1);
        return $this->upload($filename, $retries - 1);
      } else {
        $uploader->abort();
        return false;
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

    foreach($iterator as $object) {
      if($this->config->checkIfArchiveExpired($object['Key'])) {
        $this->client()->deleteObject(array(
          'Bucket' => $config['bucket'],
          'Key' => $object['Key']
        ));
        Logger::append(basename($object['Key']) . ' removed!');
      }
    }
  }
}