<?php
namespace nekoBackup\Event;

use Symfony\Component\EventDispatcher\Event;

class Action extends Event
{
  protected $action;

  public function __construct($action)
  {
    $this->action = $action;
  }

  public function getAction()
  {
    return $this->action;
  }
}