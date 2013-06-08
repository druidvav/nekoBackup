<?php
namespace nekoBackup\BasicDriver;

use nekoBackup\Event\FileReady as FileReadyEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

use nekoBackup\Backup;

class Driver
{
  protected $config;
  protected $dispatcher;

  public function __construct(EventDispatcher $dispatcher)
  {
    $dispatcher->addSubscriber(new EventSubscriber($this));
    $this->dispatcher = $dispatcher;
    $this->config = Backup::get()->config;
  }

  public function getDispatcher()
  {
    return $this->dispatcher;
  }

  public function getConfig()
  {
    return $this->config;
  }

  public function reportFileReady($filename)
  {
    $this->getDispatcher()->dispatch('nekobackup.file-ready', new FileReadyEvent($filename));
  }

  public function prepareFilename($name, $ext = '', $postfix = '')
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
}
