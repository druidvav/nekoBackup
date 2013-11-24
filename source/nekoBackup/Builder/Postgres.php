<?php
namespace nekoBackup\Builder;

use nekoBackup\Archive;

class Postgres extends AbstractBuilder
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
      $archive = new Archive\Postgres($this->config, $this->title . '-' . $db);
      $archive->setDatabase($db);
      $archive->setDatabaseAccess($this->section['sh_user']);
      $this->archives[] = $archive;
    }
  }

  protected function getDatabaseList(&$config)
  {
    $list = array();
    $databases = `su - {$config['sh_user']} -c "psql --quiet --no-align --tuples-only --dbname=postgres --command=\"SELECT datname FROM pg_database WHERE datistemplate IS FALSE AND datallowconn IS TRUE;\""`;
    foreach(explode("\n", $databases) as $db) {
      if(empty($db)) continue;
      $list[] = $db;
    }
    return $list;
  }

  protected function handleSchedule($schedule, $cleanup)
  {
    if(!is_string($schedule)) {
      throw new \Exception('Unsupported schedule format.');
    }

    foreach($this->archives as $id => &$archive) {
      /* @var Archive\Postgres $archive */
      if($this->config->checkScheduleMatch($schedule)) {
        $archive->setExpiresIn($cleanup);
      } else {
        unset($this->archives[$id]);
      }
    }
  }
}