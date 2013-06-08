<?php
namespace nekoBackup\BasicDriver;

use nekoBackup\Event\Action as ActionEvent;
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
        'executeAction', 0
      )
    );
  }

  public function executeAction(ActionEvent $event)
  {
    $config = \nekoBackup\Backup::get()->config;

    switch($event->getAction()) {
      case 'system':
        $action = new Action\SystemAction($this->driver);
        break;
      case 'directory':
        $action = new Action\DirectoryAction($this->driver);
        break;
      case 'subdirectory':
        $action = new Action\SubdirectoryAction($this->driver);
        break;
      case 'database':
        $action = new Action\DatabaseAction($this->driver);
        break;
      case 'cleanup':
        $action = new Action\CleanupAction($this->driver);
        break;
      default:
        return;
    }

    $action->execute($config[$event->getAction()], $event->getPeriod());
  }
}