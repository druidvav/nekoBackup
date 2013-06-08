<?php
namespace nekoBackup;

use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class DriverAbstract
{
  protected $config;
  protected $dispatcher;

  public function __construct(EventDispatcher $dispatcher)
  {
    $this->dispatcher = $dispatcher;
  }

  public function getDispatcher()
  {
    return $this->dispatcher;
  }
}
