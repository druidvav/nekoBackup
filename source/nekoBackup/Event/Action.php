<?php
namespace nekoBackup\Event;

use Symfony\Component\EventDispatcher\Event;

class Action extends Event
{
  protected $action;
  protected $period;

  public function __construct($action, $period)
  {
    $this->action = $action;
    $this->period = $period;
  }

  public function getAction()
  {
    return $this->action;
  }

  public function getPeriod()
  {
    return $this->period;
  }
}