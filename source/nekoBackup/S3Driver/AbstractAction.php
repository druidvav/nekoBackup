<?php
namespace nekoBackup\S3Driver;

use nekoBackup\Config;
use Aws\S3\S3Client;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

abstract class AbstractAction
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

  public function client()
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
}