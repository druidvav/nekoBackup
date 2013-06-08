<?php
namespace nekoBackup\S3Driver;

use nekoBackup\Event\Action as ActionEvent;
use nekoBackup\Event\FileReady as FileReadyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
  protected $driver;

  public function __construct(Driver $driver)
  {
    $this->driver = $driver;
  }

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