<?php
namespace nekoBackup\S3Driver\Action;

use Aws\S3\S3Client;
use nekoBackup\BackupLogger;
use nekoBackup\BasicDriver\Action\CleanupAction as BasicCleanupAction;

use nekoBackup\BasicDriver\Action;

class CleanupAction extends BasicCleanupAction
{
  /**
   * @var S3Client
   */
  protected $client;

  public function setClient($client)
  {
    $this->client = $client;
  }

  public function execute()
  {
    $iterator = $this->client->getIterator('ListObjects', array(
      'Bucket' => $this->config['bucket'],
      'Prefix' => $this->config['directory']
    ));

    $directories = array();
    foreach($iterator as $object) {
      $dirs = preg_replace('#^' . preg_quote($this->config['directory']) . '#', '', $object['Key']);
      $dirs = explode('/', $dirs);
      $directories[$dirs[0]] = $dirs[0];
    }

    foreach($directories as $dir) {
      if($this->checkDateDirectory($dir)) {
        BackupLogger::append('s3 > ' . $dir . ' > actual');
        continue;
      }

      $objects = $this->client->listObjects(array(
        'Bucket' => $this->config['bucket'],
        'Prefix' => $this->config['directory'] . $dir//  . '/'
      ));

      if(empty($objects['Contents'])) {
        BackupLogger::append('s3 > ' . $dir . ' > WTF');
        continue;
      }

      $result = $this->client->deleteObjects(array(
        'Bucket' => $this->config['bucket'],
        'Objects' => $objects['Contents']
      ));

      if(sizeof($objects['Contents']) == sizeof($result['Deleted'])) {
        BackupLogger::append('s3 > ' . $dir . ' > removed');
      } else {
        BackupLogger::append('s3 > ' . $dir . ' > error while removing!');
      }
    }
  }
}