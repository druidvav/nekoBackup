<?php
namespace nekoBackup;

use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class DriverAbstract
{
  protected $config;
  protected $dispatcher;

  public function __construct(EventDispatcher $dispatcher, $config = null)
  {
    $this->dispatcher = $dispatcher;
    $this->config = $config;
  }

  public function getDispatcher()
  {
    return $this->dispatcher;
  }

  public function getConfig()
  {
    return $this->config;
  }
}
