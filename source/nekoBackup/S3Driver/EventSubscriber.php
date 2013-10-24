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
    $this->uploadFile($event->getFilename());
  }

  protected function uploadFile($filename, $retries = 3)
  {
    $action = new UploadAction($this->config);
    $action->execute($filename, $retries);
  }

//  public function executeAction(ActionEvent $event)
//  {
//    if ($event->getAction() == 'cleanup') {
//      $this->cleanup();
//    }
//  }
//
//  public function cleanup()
//  {
//    $action = new Action\CleanupAction($this);
//    $action->execute();
//  }
}