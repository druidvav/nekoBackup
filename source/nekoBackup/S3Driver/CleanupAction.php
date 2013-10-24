<?php
namespace nekoBackup\S3Driver;

use nekoBackup\Logger;

class CleanupAction extends AbstractAction
{
  public function execute()
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