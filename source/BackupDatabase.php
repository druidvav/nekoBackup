<?php
class BackupDatabase
{
  public static function execute($config, $period)
  {
    foreach($config as $pkg => $pkg_config)
    {
      BackupLogger::indent($pkg);

      if($pkg_config['period'] == $period)
      {
        self::executeSingle($pkg, $pkg_config);
      }
      else
      {
        BackupLogger::append("skipping ({$pkg_config['period']})", 1);
      }

      BackupLogger::back();
    }
  }

  public static function executeSingle($pkg, $config)
  {
    BackupLogger::append('getting database list..', 1);

    switch($config['type'])
    {
      case 'mysql': $list = self::getMysqlDatabaseList($config); break;
      case 'postgres': $list = self::getPostgresDatabaseList($config); break;
      default: $list = array(); break;
    }

    BackupLogger::append(' ..done', 1);

    foreach($list as $db)
    {
      if(!empty($config['exclude']) && in_array($db, $config['exclude']))
      {
        BackupLogger::append("$db excluded", 1);
        continue;
      }

      BackupLogger::indent($db);

      BackupLogger::append("archiving..", 1);
      $filename = Backup::get()->prepareFilename($pkg . '-' . $db, 'sql.gz');

      switch($config['type'])
      {
        case 'mysql': self::dumpMysqlDatabase($config, $db, $filename); break;
        case 'postgres': self::dumpPostgresDatabase($config, $db, $filename); break;
      }

      BackupEvents::trigger('file', array('filename' => $filename));
      BackupLogger::append(" ..done", 1);

      BackupLogger::back();
    }
  }

  protected static function getMysqlDatabaseList(&$config)
  {
    if(!($conn = mysql_connect($config['hostname'], $config['username'], $config['password'])))
    {
      throw new Exception('Cannot connect to mysql server!');
    }

    if(!($res = mysql_query('show databases', $conn)))
    {
      throw new Exception('Cannot read database list');
    }

    $list = array();
    while($row = mysql_fetch_assoc($res))
    {
      $list[] = $row['Database'];
    }

    $exclude_tables = array();
    if(!empty($config['exclude'])) foreach($config['exclude'] as $row)
    {
      $row = explode('.', $row, 2);
      if(sizeof($row) == 2)
      {
        $exclude_tables[$row[0]][] = $row[1];
      }
    }

    $config['exclude_tables'] = array();
    foreach($exclude_tables as $db => $tables)
    {
      mysql_select_db($db, $conn);

      foreach($tables as $table)
      {
        $res = mysql_query('SHOW tables like "' . mysql_real_escape_string($table) . '"', $conn);
        while($row = mysql_fetch_array($res))
        {
          $config['exclude_tables'][$db][] = '--ignore-table=' . $db . '.' . $row[0];
        }
      }
    }
    return $list;
  }

  protected static function dumpMysqlDatabase($config, $db, $filename)
  {
    if(!empty($config['exclude_tables'][$db]))
    {
      $config['exclude_tables'][$db] = implode(' ', $config['exclude_tables'][$db]);
    }
    else
    {
      $config['exclude_tables'][$db] = '';
    }

    `mysqldump -u {$config['username']} -p{$config['password']} -h {$config['hostname']} {$db} {$config['exclude_tables'][$db]} | gzip -c > "{$filename}"`; // --single-transaction
  }

  protected static function getPostgresDatabaseList(&$config)
  {
    $list = array();

    $databases = `su - {$config['sh_user']} -c "psql --quiet --no-align --tuples-only --dbname=postgres --command=\"SELECT datname FROM pg_database WHERE datistemplate IS FALSE AND datallowconn IS TRUE;\""`;
    foreach(explode("\n", $databases) as $db)
    {
      if(empty($db)) continue;

      $list[] = $db;
    }

    return $list;
  }

  protected static function dumpPostgresDatabase($config, $db, $filename)
  {
    `su - {$config['sh_user']} -c "pg_dump -c --column-inserts --inserts {$db}" | gzip -c > "{$filename}"`;
  }
}