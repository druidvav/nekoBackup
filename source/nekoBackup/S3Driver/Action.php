<?php
namespace nekoBackup\S3Driver;

use Aws\S3\S3Client;
use nekoBackup\Config;
use nekoBackup\DriverAbstract;
use nekoBackup\BackupLogger;
use nekoBackup\BasicDriver\Action as ActionAbstract;

abstract class Action extends ActionAbstract
{
  /**
   * @var S3Client
   */
  private $client;

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

  protected function getS3Config()
  {
    $config = Config::get('s3');
    $config['directory'] = $config['directory'] . "/";
    return $config;
  }
}