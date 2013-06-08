<?php
namespace nekoBackup\BasicDriver\Action;

use nekoBackup\BackupLogger;

use nekoBackup\BasicDriver\Action;
use nekoBackup\Event\FileReady as FileReadyEvent;

class DatabaseAction extends Action
{
  public function execute($period)
  {
    foreach($this->getActionConfig() as $pkg => $pkg_config) {
      $this->indent($pkg);

      if($pkg_config['period'] == $period) {
        $this->executeSingle($pkg, $pkg_config);
      } else {
        $this->write("skipping ({$pkg_config['period']})", 1);
      }

      $this->back();
    }
  }

  public function executeSingle($pkg, $config)
  {
    $this->write('getting database list..', 1);

    switch($config['type']) {
      case 'mysql': $list = $this->getMysqlDatabaseList($config); break;
      case 'postgres': $list = $this->getPostgresDatabaseList($config); break;
      default: $list = array(); break;
    }

    $this->write(' ..done', 1);

    foreach($list as $db) {
      if(!empty($config['exclude']) && in_array($db, $config['exclude'])) {
        $this->write("$db excluded", 1);
        continue;
      }

      $this->indent($db);

      $this->write("archiving..", 1);
      $filename = $this->prepareFilename($pkg . '-' . $db, 'sql.gz');

      switch($config['type']) {
        case 'mysql': $this->dumpMysqlDatabase($config, $db, $filename); break;
        case 'postgres': $this->dumpPostgresDatabase($config, $db, $filename); break;
      }

      global $dispatcher;
      $dispatcher->dispatch('nekobackup.file-ready', new FileReadyEvent($filename));

      $this->write(" ..done", 1);

      $this->back();
    }
  }

  protected function getMysqlDatabaseList(&$config)
  {
    if(!($conn = mysql_connect($config['hostname'], $config['username'], $config['password']))){
      throw new \Exception('Cannot connect to mysql server!');
    }

    if(!($res = mysql_query('show databases', $conn))) {
      throw new \Exception('Cannot read database list');
    }

    $list = array();
    while($row = mysql_fetch_assoc($res)) {
      $list[] = $row['Database'];
    }

    $exclude_tables = array();
    if(!empty($config['exclude'])) foreach($config['exclude'] as $row) {
      $row = explode('.', $row, 2);
      if(sizeof($row) == 2) {
        $exclude_tables[$row[0]][] = $row[1];
      }
    }

    $config['exclude_tables'] = array();
    foreach($exclude_tables as $db => $tables) {
      mysql_select_db($db, $conn);

      foreach($tables as $table) {
        $res = mysql_query('SHOW tables like "' . mysql_real_escape_string($table) . '"', $conn);
        while($row = mysql_fetch_array($res)) {
          $config['exclude_tables'][$db][] = '--ignore-table=' . $db . '.' . $row[0];
        }
      }
    }
    return $list;
  }

  protected function dumpMysqlDatabase($config, $db, $filename)
  {
    if(!empty($config['exclude_tables'][$db])) {
      $config['exclude_tables'][$db] = implode(' ', $config['exclude_tables'][$db]);
    } else {
      $config['exclude_tables'][$db] = '';
    }

    exec("mysqldump -u {$config['username']} -p{$config['password']} -h {$config['hostname']} {$db} {$config['exclude_tables'][$db]} | gzip -c > \"{$filename}\""); // --single-transaction
  }

  protected function getPostgresDatabaseList(&$config)
  {
    $list = array();

    $databases = `su - {$config['sh_user']} -c "psql --quiet --no-align --tuples-only --dbname=postgres --command=\"SELECT datname FROM pg_database WHERE datistemplate IS FALSE AND datallowconn IS TRUE;\""`;
    foreach(explode("\n", $databases) as $db) {
      if(empty($db)) continue;
      $list[] = $db;
    }

    return $list;
  }

  protected function dumpPostgresDatabase($config, $db, $filename)
  {
    exec("su - {$config['sh_user']} -c \"pg_dump -c --column-inserts --inserts {$db}\" | gzip -c > \"{$filename}\"");
  }
}