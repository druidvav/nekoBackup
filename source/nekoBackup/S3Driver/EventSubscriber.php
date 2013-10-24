<?php
namespace nekoBackup\S3Driver;

use nekoBackup\Config;
use nekoBackup\Event\Action as ActionEvent;
use nekoBackup\Event\FileReady as FileReadyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
  protected $config;

  public function __construct(Config $config)
  {
    $this->config = $config;
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
    $action = new UploadAction($this->config);
    $action->execute($event->getFilename(), 3);
  }

  public function executeAction(ActionEvent $event)
  {
    if ($event->getAction() == 'cleanup') {
      $action = new CleanupAction($this->config);
      $action->execute();
    }
  }
}