<?php
namespace nekoBackup\S3Driver;

use nekoBackup\EventSubscriberAbstract;
use nekoBackup\Event\Action as ActionEvent;
use nekoBackup\Event\FileReady as FileReadyEvent;

class EventSubscriber extends EventSubscriberAbstract
{
  /**
   * @var $driver Driver
   */
  protected $driver;

  public static function getSubscribedEvents()
  {
    return array(
      'nekobackup.action' => array(
        'executeAction', 10
      ),
      'nekobackup.file-ready' => array(
        'onFileReady', 0
      ),
    );
  }

  public function onFileReady(FileReadyEvent $event)
  {
    $this->driver->uploadFile($event->getFilename());
  }

  public function executeAction(ActionEvent $event)
  {
    if ($event->getAction() == 'cleanup') {
      $this->driver->cleanup();
    }
  }
}