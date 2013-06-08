<?php
namespace nekoBackup\BasicDriver;

use nekoBackup\EventSubscriberAbstract;
use nekoBackup\Event\Action as ActionEvent;

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
        'executeAction', 0
      )
    );
  }

  public function executeAction(ActionEvent $event)
  {
    $this->driver->executeAction($event->getAction(), $event->getPeriod());
  }
}