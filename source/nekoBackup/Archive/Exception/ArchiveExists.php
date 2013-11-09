<?php
namespace nekoBackup\Archive\Exception;

use Exception;

class ArchiveExists extends Exception
{
  protected $archiveFilename;

  public function __construct($archiveFilename)
  {
    $this->archiveFilename = $archiveFilename;
    parent::__construct();
  }

  public function getArchiveFilename()
  {
    return $this->archiveFilename;
  }
}