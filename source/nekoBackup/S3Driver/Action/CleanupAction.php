<?php
namespace nekoBackup\S3Driver\Action;

use nekoBackup\S3Driver\Action;
use nekoBackup\BasicDriver\Action\CleanupAction as BasicCleanupAction;

class CleanupAction extends Action
{
  public function execute()
  {
    $config = $this->getS3Config();

    $iterator = $this->client()->getIterator('ListObjects', array(
      'Bucket' => $config['bucket'],
      'Prefix' => $config['directory']
    ));

    $directories = array();
    foreach($iterator as $object) {
      $dirs = preg_replace('#^' . preg_quote($config['directory']) . '#', '', $object['Key']);
      $dirs = explode('/', $dirs);
      if(empty($dirs[0])) continue;
      $directories[$dirs[0]] = $dirs[0];
    }

    foreach($directories as $dir) {
      if($this->getBasicAction()->checkDateDirectory($dir)) {
        $this->write('s3 > ' . $dir . ' > actual');
        continue;
      }

      $objects = $this->client()->listObjects(array(
        'Bucket' => $config['bucket'],
        'Prefix' => $config['directory'] . $dir//  . '/'
      ));

      if(empty($objects['Contents'])) {
        $this->write('s3 > ' . $dir . ' > WTF');
        continue;
      }

      $result = $this->client()->deleteObjects(array(
        'Bucket' => $config['bucket'],
        'Objects' => $objects['Contents']
      ));

      if(sizeof($objects['Contents']) == sizeof($result['Deleted'])) {
        $this->write('s3 > ' . $dir . ' > removed');
      } else {
        $this->write('s3 > ' . $dir . ' > error while removing!');
      }
    }
  }

  protected function getBasicAction()
  {
    return (new BasicCleanupAction($this->driver));
  }
}