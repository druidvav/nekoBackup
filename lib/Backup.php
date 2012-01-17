<?php
class Backup
{
  protected $config;
  protected $week_day;
  protected $month_day;
  protected $callbacks;

  public function __construct($config)
  {
    $this->config = $config;

    // TODO Events
    // TODO Errors handling
  }

  public function bind($event, $callback)
  {
    $this->callbacks[$event] = $callback;
  }

  public function trigger($event, $options = array())
  {
    if(empty($this->callbacks[$event])) return;

    return call_user_func_array($this->callbacks[$event], array(&$this, $options));
  }

  public function run($date)
  {
    $this->week_day = date('N', $date);
    $this->month_day = date('j', $date);

    $this->log("Backup started: DoW={$this->week_day}, DoM={$this->month_day}.", 2);

    if(!$this->checkSchedule($this->config['schedule']))
    {
      $this->log("Backup is not scheduled for today.", 2);
    }

    foreach($this->config as $section => &$config)
    {
      switch($section)
      {
        default: break;
        case 'system':
        case 'home':
        case 'websites':
        case 'mysql':
        case 'postgres':
        case 'special':
          $this->logIndent($section);
          if(empty($config['schedule']) || $this->checkSchedule($config['schedule']))
          {
            $this->log("started", 2);

            $method = 'backup' . ucfirst($section);
            $this->$method($config);

            $this->log("finished", 2);
          }
          else
          {
            $this->log("skipping ({$config['schedule']})", 2);
          }
          $this->logIndentBack();
          break;
      }
    }

    $this->log("Backup finished.", 2);
  }

  public function backupSystem(&$config)
  {
    $this->log("archiving..", 1);
    $filename = $this->prepareFilename('system', 'tar.bz2');
    $this->archive($filename, $config['include'], $config['exclude']);
    $this->log(" ..done", 1);

    $this->trigger('file', array('filename' => $filename));

    if($config['packages'] == 'Debian')
    {
      $this->log("reading packages for Debian..", 1);
      $filename = $this->prepareFilename('system-packages', 'txt.bz2');
      `dpkg --get-selections | grep -v deinstall | bzip2 --best > {$filename}`;
      $this->log(" ..done", 1);

      $this->trigger('file', array('filename' => $filename));
    }
    else
    {
      $this->log("unknown packaging mode: {$config['packages']}", 3);
    }
  }

  public function backupHome(&$config)
  {
    $dirs = `ls /home/`;
    foreach(explode("\n", $dirs) as $dir)
    {
      if(empty($dir)) continue;

      $this->logIndent($dir);

      if($this->checkScheduleOverride($config['schedule_override'][$dir]))
      {
        $exclude = array();
        foreach($config['exclude'] as $exclude_dir)
        {
          $exclude[] = '/home/' . $dir . '/' . $exclude_dir;
        }

        $this->log("archiving..", 1);
        $filename = $this->prepareFilename('home-' . $dir, 'tar.bz2');
        $this->archive($filename, array('/home/' . $dir), $exclude);
        $this->log(" ..done", 1);

        $this->trigger('file', array('filename' => $filename));
      }

      $this->logIndentBack();
    }
  }

  public function backupWebsites(&$config)
  {
    $dirs = `ls {$config['list']}`;
    foreach(explode("\n", $dirs) as $dir)
    {
      if(empty($dir)) continue;
      if(!preg_match($config['preg'], $dir, $match)) continue;

      $user = $match[2]; // TODO Support for empty
      $prefix = "website-{$user}-";

      $this->logIndent($user);

      //if($this->checkScheduleOverride($config['schedule_override'][$user], $config['schedule_default']))
      //{
        $subdirs = `ls -A {$match[1]}`;
        foreach(explode("\n", $subdirs) as $subdir)
        {
          if(empty($subdir)) continue;

          $this->logIndent($subdir);

          $default_schedule = !empty($config['schedule_override'][$user]) ? $config['schedule_override'][$user] : $config['schedule_default'];
          if($this->checkScheduleOverride($config['schedule_override']["{$user}_{$subdir}"], $default_schedule))
          {
            $this->log("archiving..", 1);
            $filename = $this->prepareFilename($prefix . $subdir, 'tar.bz2');
            $this->archive($filename, array($match[1] . '/' . $subdir), array());
            $this->log(" ..done", 1);

            $this->trigger('file', array('filename' => $filename));
          }

          $this->logIndentBack();
        }
      //}

      $this->logIndentBack();
    }
  }

  public function backupSpecial(&$config)
  {
    foreach($config as $pkg => $pkg_config)
    {
      if($pkg == 'schedule') continue;

      $this->logIndent($pkg);

      if(empty($pkg_config['schedule']) || $this->checkSchedule($pkg_config['schedule']))
      {
        $this->log('archiving..', 1);
        $filename = $this->prepareFilename('special-' . $pkg, 'tar.bz2');
        $this->archive($filename, $pkg_config['include'], $pkg_config['exclude']);
        $this->log(' ..done', 1);

        $this->trigger('file', array('filename' => $filename));
      }
      else
      {
        $this->log("skipping ({$pkg_config['schedule']})", 1);
      }

      $this->logIndentBack();
    }
  }

  public function backupMysql(&$cfg)
  {
    $this->log("connecting..", 1);

    if(!($conn = mysql_connect($cfg['hostname'], $cfg['username'], $cfg['password'])))
    {
      throw new Exception('Cannot connect to mysql server!');
    }

    if(!($res = mysql_query('show databases', $conn)))
    {
      throw new Exception('Cannot read database list');
    }

    $this->log(" ..done", 1);

    while($row = mysql_fetch_assoc($res))
    {
      $db = $row['Database'];

      if(in_array($db, $cfg['exclude']))
      {
        $this->log("$db excluded", 1);
        continue;
      }

      $this->logIndent($db);

      if($this->checkScheduleOverride($cfg['schedule_override'][$db]))
      {
        $this->log("archiving..", 1);
        $filename = $this->prepareFilename('mysql-' . $row['Database'], 'sql.bz2');
        `mysqldump -u {$cfg['username']} -p{$cfg['password']} -h {$cfg['hostname']} {$row['Database']} | bzip2 -c > "{$filename}"`;
        $this->log(" ..done", 1);

        $this->trigger('file', array('filename' => $filename));
      }

      $this->logIndentBack();
    }
  }

  public function backupPostgres(&$cfg)
  {
    $databases = `su - {$cfg['sh_user']} -c "psql --quiet --no-align --tuples-only --dbname=postgres --command=\"SELECT datname FROM pg_database WHERE datistemplate IS FALSE AND datallowconn IS TRUE;\""`;
    foreach(explode("\n", $databases) as $db)
    {
      if(empty($db)) continue;

      if(in_array($db, $cfg['exclude']))
      {
        $this->log("$db excluded", 1);
        continue;
      }

      $this->logIndent($db);

      if($this->checkScheduleOverride($cfg['schedule_override'][$db]))
      {
        $this->log("archiving..", 1);
        $filename = $this->prepareFilename('postgres-' . $db, 'sql.bz2');
        `su - {$cfg['sh_user']} -c "pg_dump -c --column-inserts --inserts {$db}" | bzip2 -c > "{$filename}"`;
        $this->log(" ..done", 1);

        $this->trigger('file', array('filename' => $filename));
      }

      $this->logIndentBack();
    }
  }

  protected function prepareFilename($name, $ext = '', $postfix = '')
  {
    $filename =
      $this->config['storage'] // Storage path
        . date('Ymd/') // Date directory
        . date('Ymd.His.') // Datetime filename prefix
        . $name // Requested filename
        . ($postfix ? '.' . $postfix : '') // Postfix
        . '.' . $ext; // Extension

    // Creating directory if it doesn't exist
    if(!is_dir(dirname($filename)))
    {
      mkdir(dirname($filename));
    }

    //
    if(is_file($filename))
    {
      return $this->prepareFilename($name, $ext, $postfix + 1);
    }

    return $filename;
  }

  protected function archive($archive, $include, $exclude = array())
  {
    $cl_include = implode(' ', $include);

    // Global excludes
    if(!empty($this->config['exclude']))
    {
      $exclude = array_merge($exclude, $this->config['exclude']);
    }

    // If excluded dir is included -- we must remove it from exclusion
    foreach($exclude as &$dir) if(in_array($dir, $include)) $dir = '';

    $cl_exclude = '';
    foreach($exclude as $dir)
    {
      if(!empty($dir)) $cl_exclude .= ' --exclude="' . $dir . '"';
    }

    return `tar {$cl_exclude} -cjpf {$archive} {$cl_include} 2>&1`;
  }

  protected function checkSchedule($schedules)
  {
    $result = false;

    $schedules = explode('|', trim($schedules));
    foreach($schedules as $schedule)
    {
      switch($schedule{0})
      {
        // Disabled
        case 'N': $result = $result || false; break;
        // Daily
        case 'd': $result = $result || true; break;
        // Weekly
        case 'w':
          if($schedule{1} == '[')
            $days = explode(',', substr($schedule, 2, -1));
          else
            $days = array(1);
          $result = $result || in_array($this->week_day, $days);
          break;
        // Monthly
        case 'w':
          if($schedule{1} == '[')
            $days = explode(',', substr($schedule, 2, -1));
          else
            $days = array(1);
          $result = $result || in_array($this->month_day, $days);
          break;
      }
    }

    return $result;
  }

  protected function checkScheduleOverride(&$override, $schedule = '')
  {
    if(!empty($override))
    {
      $this->log("schedule override", 1);
      $schedule = $override;
    }

    if(!empty($schedule) && !$this->checkSchedule($schedule))
    {
      $this->log("skipping ({$schedule})", 2);
      return false;
    }

    return true;
  }

  protected $log_indent = array();

  // Levels: 1 - notice, 2 - status update, 3 - warning, 4 - error
  public function log($message, $level = 1)
  {
    $date = date("Y.m.d H:i:s");
    $indent = $this->log_indent ? implode(' > ', $this->log_indent) . ' > ' : '';
    echo "$date > $level > {$indent}$message \n";
  }

  protected function logIndent($group) { $this->log_indent[] = $group; }
  protected function logIndentBack() { array_pop($this->log_indent); }
}
