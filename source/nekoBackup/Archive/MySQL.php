<?php
namespace nekoBackup\Archive;

class MySQL extends AbstractArchive
{
  protected $database;
  protected $dbAccess;
  protected $expiresIn = 7;

  public function setDatabase($db)
  {
    $this->database = $db;
  }

  public function setDatabaseAccess($hostname, $username, $password)
  {
    $this->dbAccess = array(
      'host' => $hostname,
      'user' => $username,
      'pass' => $password
    );
  }

  public function create()
  {
    $this->archiveFilename = $this->generateArchivePath('sql.gz');

//    if(!empty($config['exclude_tables'][$db])) {
//      $config['exclude_tables'][$db] = implode(' ', $config['exclude_tables'][$db]);
//    } else {
//      $config['exclude_tables'][$db] = '';
//    }

    $cmd = "mysqldump -u {$this->dbAccess['user']} -p{$this->dbAccess['pass']} -h {$this->dbAccess['host']} {$this->database} | gzip -c > \"{$this->archiveFilename}\"";
    // --single-transaction
    system($cmd, $status);
    return $status == 0;
  }
}