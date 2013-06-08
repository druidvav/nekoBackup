<?php
namespace nekoBackup;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class EventSubscriberAbstract implements EventSubscriberInterface
{
  protected $driver;

  public function __construct(DriverAbstract $driver)
  {
    $this->driver = $driver;
  }
}