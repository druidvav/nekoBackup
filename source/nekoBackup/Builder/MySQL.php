<?php
namespace nekoBackup\Builder;

use nekoBackup\Archive;

class MySQL extends AbstractBuilder
{
  public function build()
  {
    $this->prepareArchives();
    $this->handleSchedule($this->section['schedule'], $this->section['cleanup']);
  }

  public function prepareArchives()
  {
    $list = $this->getDatabaseList($this->section);
    foreach($list as $db) {
      if(!empty($this->section['exclude']) && in_array($db, $this->section['exclude'])) {
        continue;
      }
      $archive = new Archive\MySQL($this->config, $this->title . '-' . $db);
      $archive->setDatabase($db);
      $archive->setDatabaseAccess($this->section['hostname'], $this->section['username'], $this->section['password']);
      $this->archives[] = $archive;
    }
  }

  protected function getDatabaseList(&$config)
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

//    $exclude_tables = array();
//    if(!empty($config['exclude'])) foreach($config['exclude'] as $row) {
//      $row = explode('.', $row, 2);
//      if(sizeof($row) == 2) {
//        $exclude_tables[$row[0]][] = $row[1];
//      }
//    }

//    $config['exclude_tables'] = array();
//    foreach($exclude_tables as $db => $tables) {
//      mysql_select_db($db, $conn);
//
//      foreach($tables as $table) {
//        $res = mysql_query('SHOW tables like "' . mysql_real_escape_string($table) . '"', $conn);
//        while($row = mysql_fetch_array($res)) {
//          $config['exclude_tables'][$db][] = '--ignore-table=' . $db . '.' . $row[0];
//        }
//      }
//    }
    return $list;
  }

  protected function handleSchedule($schedule, $cleanup)
  {
    if(!is_string($schedule)) {
      throw new \Exception('Unsupported schedule format.');
    }

    foreach($this->archives as $id => &$archive) {
      /* @var Archive\MySQL $archive */
      if($this->config->checkScheduleMatch($schedule)) {
        $archive->setExpiresIn($cleanup);
      } else {
        unset($this->archives[$id]);
      }
    }
  }
}