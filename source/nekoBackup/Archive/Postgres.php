<?php
namespace nekoBackup\Archive;

class Postgres extends AbstractArchive
{
  protected $database;
  protected $dbAccess;
  protected $expiresIn = 7;

  public function setDatabase($db)
  {
    $this->database = $db;
  }

  public function setDatabaseAccess($shellUser)
  {
    $this->dbAccess = array(
      'sh_user' => $shellUser,
    );
  }

  public function create()
  {
    $this->archiveFilename = $this->generateArchivePath('sql.gz');
    @unlink("{$this->archiveFilename}.tmp");
    $cmd = "su - {$this->dbAccess['sh_user']} -c \"pg_dump -c --column-inserts --inserts {$this->database}\" | gzip -c > \"{$this->archiveFilename}.tmp\"";
    system($cmd, $status);
    if($status == 0) {
      rename("{$this->archiveFilename}.tmp", $this->archiveFilename);
    }
    return $status == 0;
  }
}