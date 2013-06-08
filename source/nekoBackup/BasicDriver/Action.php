<?php
namespace nekoBackup\BasicDriver;

use nekoBackup\BackupLogger;

abstract class Action
{
  protected $driver;
  protected $config;

  public function __construct(Driver $driver)
  {
    $this->driver = $driver;
    $this->config = $driver->getConfig();
  }

  public function prepareFilename($name, $ext = '')
  {
    return $this->driver->prepareFilename($name, $ext);
  }
}