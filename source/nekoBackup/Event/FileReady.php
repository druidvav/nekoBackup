<?php
namespace nekoBackup\Event;

use Symfony\Component\EventDispatcher\Event;

class FileReady extends Event
{
  protected $filename;

  public function __construct($filename)
  {
    $this->filename = $filename;
  }

  public function getFilename()
  {
    return $this->filename;
  }
}