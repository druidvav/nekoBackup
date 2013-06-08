<?php
namespace nekoBackup\S3Driver;

use nekoBackup\DriverAbstract;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Driver extends DriverAbstract
{
  public function __construct(EventDispatcher $dispatcher)
  {
    parent::__construct($dispatcher);
    $dispatcher->addSubscriber(new EventSubscriber($this));
  }

  public function uploadFile($filename, $retries = 3)
  {
    $action = new Action\UploadAction($this);
    $action->execute($filename, $retries);
  }

  public function cleanup()
  {
    $action = new Action\CleanupAction($this);
    $action->execute();
  }
}
