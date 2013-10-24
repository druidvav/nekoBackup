<?php
namespace nekoBackup\S3Driver;

use nekoBackup\Logger;
use Aws\Common\Exception\MultipartUploadException;

class UploadAction extends AbstractAction
{
  public function execute($filename, $retries = 3)
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
//      Logger::append('removing local file..');
//      unlink($filename);
    } catch (MultipartUploadException $e) {
      if($retries > 0) {
        Logger::append('failed: ' . $e->getMessage());
        Logger::append('retrying...', 1);
        $this->execute($filename, $retries - 1);
        return;
      } else {
        $uploader->abort();
        Logger::append('failed: ' . $e->getMessage());
        exit;
      }
    }
  }
}