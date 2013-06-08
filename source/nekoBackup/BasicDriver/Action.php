<?php
namespace nekoBackup\BasicDriver;

use nekoBackup\BackupLogger;

abstract class Action
{
  protected $driver;
  private $config;
  private $actionConfig;

  public function __construct(Driver $driver)
  {
    $this->driver = $driver;
    $this->config = $driver->getConfig();
  }

  public function setActionConfig($config)
  {
    return $this->actionConfig = $config;
  }

  protected function getActionConfig()
  {
    return $this->actionConfig;
  }

  protected function getGlobalConfig()
  {
    return $this->config;
  }

  protected function dispatcher()
  {
    return $this->driver->getDispatcher();
  }

  protected function write($string)
  {
    BackupLogger::append($string);
  }

  protected function indent($string)
  {
    BackupLogger::indent($string);
  }

  protected function back()
  {
    BackupLogger::back();
  }

  protected function prepareFilename($name, $ext = '', $postfix = '')
  {
    $filename =
      $this->config['storage'] // Storage path
        . date('Ymd/') // Date directory
        . date('Ymd.His.') // Datetime filename prefix
        . $name // Requested filename
        . ($postfix ? '.' . $postfix : '') // Postfix
        . '.' . $ext; // Extension

    // Creating directory if it doesn't exist
    if(!is_dir(dirname($filename))) {
      mkdir(dirname($filename));
    }

    if(is_file($filename)) {
      return $this->prepareFilename($name, $ext, $postfix + 1);
    }

    return $filename;
  }

  protected function reportFileReady($filename)
  {
    $this->driver->reportFileReady($filename);
  }
}