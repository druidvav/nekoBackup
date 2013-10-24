<?php
namespace nekoBackup\Archive;

use nekoBackup\Config;

abstract class AbstractArchive
{
  protected $config;
  protected $title = '';
  protected $expiresIn = 7;

  protected $archiveFilename;

  public function __construct(Config $config, $title)
  {
    $this->title = $title;
    $this->config = $config;
  }

  public function getTitle()
  {
    return $this->title;
  }

  public function getArchiveFilename()
  {
    return $this->archiveFilename;
  }

  public function setExpiresIn($days)
  {
    $this->expiresIn = $days;
    return $this;
  }

  abstract public function create();

  protected function generateArchivePath($ext = '')
  {
    $filename =
      $this->config->get('storage') // Storage path
      . date('/Ymd/') // Date directory
      . date('Ymd.') // Datetime filename prefix
      . $this->expiresIn . '.'
      . $this->title // Requested filename
      . '.' . $ext; // Extension
    if(!is_dir(dirname($filename))) {
      mkdir(dirname($filename));
    }
    return $filename;
  }
}