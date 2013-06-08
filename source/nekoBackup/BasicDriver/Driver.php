<?php
namespace nekoBackup\BasicDriver;

use nekoBackup\DriverAbstract;
use nekoBackup\Event\FileReady as FileReadyEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Driver extends DriverAbstract
{
  public function __construct(EventDispatcher $dispatcher)
  {
    parent::__construct($dispatcher);
    $dispatcher->addSubscriber(new EventSubscriber($this));
  }

  public function executeAction($action, $period)
  {
    switch($action) {
      case 'system':       $actionObject = new Action\SystemAction($this); break;
      case 'directory':    $actionObject = new Action\DirectoryAction($this); break;
      case 'subdirectory': $actionObject = new Action\SubdirectoryAction($this); break;
      case 'database':     $actionObject = new Action\DatabaseAction($this); break;
      case 'cleanup':      $actionObject = new Action\CleanupAction($this); break;
      default: return;
    }
    $actionObject->execute($period);
  }

  public function reportFileReady($filename)
  {
    $this->getDispatcher()->dispatch('nekobackup.file-ready', new FileReadyEvent($filename));
  }
}
