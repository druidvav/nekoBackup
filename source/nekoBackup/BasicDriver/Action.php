<?php
namespace nekoBackup\BasicDriver;

use nekoBackup\Config;
use nekoBackup\DriverAbstract;
use nekoBackup\Logger;

abstract class Action
{
  protected $driver;
  protected $action;

  public function __construct(DriverAbstract $driver)
  {
    $this->driver = $driver;
  }

  protected function getActionConfig()
  {
    $config = Config::get('config');
    return $config[$this->action];
  }

  protected function getGlobalConfig()
  {
    return Config::get('config');
  }

  protected function dispatcher()
  {
    return $this->driver->getDispatcher();
  }

  protected function write($string)
  {
    Logger::append($string);
  }

  protected function indent($string)
  {
    Logger::indent($string);
  }

  protected function back()
  {
    Logger::back();
  }

  protected function prepareFilename($name, $ext = '', $postfix = '')
  {
    $config = $this->getGlobalConfig();

    $filename =
      $config['storage'] // Storage path
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