<?php
namespace nekoBackup\S3Driver\Action;

use nekoBackup\S3Driver\Action;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\Common\Exception\MultipartUploadException;

class UploadAction extends Action
{
  public function execute($filename, $retries = 3)
  {
    $global = $this->getGlobalConfig();
    $config = $this->getS3Config();

    $remote_file = $config['directory'] . str_replace($global['storage'], '', $filename);

    $uploader = $this->getUploader($filename)
      ->setBucket($config['bucket'])
      ->setKey($remote_file)
      ->setConcurrency(4)
      ->setMinPartSize(50*1024*1024) // 50 Mb
      ->build();

    $this->write('uploading file..');

    try {
      $uploader->upload();
      $this->write('complete!');
      $this->write('removing local file..');
      unlink($filename);
    } catch (MultipartUploadException $e) {
      if($retries > 0) {
        $this->write('failed: ' . $e->getMessage());
        $this->write('retrying...', 1);
        $this->execute($filename, $retries - 1);
        return;
      } else {
        $uploader->abort();
        $this->write('failed: ' . $e->getMessage());
        exit;
      }
    }
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
}