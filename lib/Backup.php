<?php
class Backup
{
  public $config;
  protected $period;
  protected $callbacks;

  public function __construct($config)
  {
    $this->config = $config;
  }

  public function bind($event, $callback)
  {
    $this->callbacks[$event] = $callback;
  }

  public function trigger($event, $options = array())
  {
    if(empty($this->callbacks[$event])) return true;

    return call_user_func_array($this->callbacks[$event], array(&$this, $options));
  }

  public function execute($date)
  {
    switch($period = $this->getDatePeriod($date))
    { // TODO: Issue #2
      case 'monthly':
        $this->execurePeriodic('monthly');
      case 'weekly':
        $this->execurePeriodic('weekly');
      case 'daily':
        $this->execurePeriodic('daily');
        break;
    }
  }

  public function execurePeriodic($period)
  {
    if(empty($period) || !isset($this->config['schedule'][$period]))
    {
      $this->log("Unknown schedule period '{$period}'", 4);
      return false;
    }

    $this->period = $period;
    $schedule = array_merge($this->config['schedule'][$period], $this->config['schedule']['managed']);

    $this->log("Backup started: {$period}.", 2);

    $this->trigger('start');
    foreach($schedule as $section)
    {
      if(empty($this->config[$section]))
      {
        $this->log("Unknown section '{$section}'", 3);
        continue;
      }

      $this->logIndent($section);
      $this->log("started", 2);

      $method = 'backup' . ucfirst($section);
      $this->$method($this->config[$section]);

      $this->log("finished", 2);
      $this->logIndentBack();
    }
    $this->trigger('finish');

    $this->log("Backup finished.", 2);

    return true;
  }

  public function backupSystem(&$config)
  {
    $this->log("archiving..", 1);
    $filename = $this->prepareFilename('system', 'tar.bz2');
    $this->archive($filename, $config['include'], $config['exclude']);
    $this->trigger('file', array('filename' => $filename));
    $this->log(" ..done", 1);


    if($config['packages'] == 'Debian')
    {
      $this->log("reading packages for Debian..", 1);
      $filename = $this->prepareFilename('system-packages', 'txt.bz2');
      `dpkg --get-selections | grep -v deinstall | bzip2 --best > {$filename}`;
      $this->trigger('file', array('filename' => $filename));
      $this->log(" ..done", 1);
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

      $exclude = array();
      foreach($config['exclude'] as $exclude_dir)
      {
        $exclude[] = '/home/' . $dir . '/' . $exclude_dir;
      }

      $this->log("archiving..", 1);
      $filename = $this->prepareFilename('home-' . $dir, 'tar.bz2');
      $this->archive($filename, array('/home/' . $dir), $exclude);
      $this->trigger('file', array('filename' => $filename));
      $this->log(" ..done", 1);

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

      $website = array();
      $website['prefix'] = 'website-' . (!empty($match[2]) ? "{$match[2]}-" : '');

      $subdirs = `ls -A {$match[1]}`;
      foreach(explode("\n", $subdirs) as $subdir)
      {
        if(empty($subdir)) continue;

        $website['id'] = (!empty($match[2]) ? $match[2] . '_' : '') . $subdir;
        $website['group'] = @$match[2];
        $website['filename'] = $website['prefix'] . $subdir;
        $website['directory'] = $match[1] . '/' . $subdir;

        $period = !empty($config['period_default']) ? $config['period_default'] : $this->period;

        if(!empty($config['period_override'][$website['id']]))
        {
          $period = $config['period_override'][$website['id']];
        }
        elseif(!empty($config['period_override'][$website['group']]))
        {
          $period = $config['period_override'][$website['group']];
        }

        $this->logIndent($website['directory']);

        if($period == $this->period)
        {
          $this->log('archiving..', 1);
          $filename = $this->prepareFilename($website['filename'], 'tar.bz2');
          $this->archive($filename, array($website['directory']), array());
          $this->trigger('file', array('filename' => $filename));
          $this->log(" ..done", 1);
        }
        else
        {
          $this->log('skipping (' . $period . ')', 1);
        }

        $this->logIndentBack();
      }
    }
  }

  public function backupSpecial(&$config)
  {
    foreach($config as $pkg => $pkg_config)
    {
      $this->logIndent($pkg);

      if($pkg_config['period'] == $this->period)
      {
        $this->log('archiving..', 1);
        $filename = $this->prepareFilename('special-' . $pkg, 'tar.bz2');
        $this->archive($filename, $pkg_config['include'], $pkg_config['exclude']);
        $this->trigger('file', array('filename' => $filename));
        $this->log(' ..done', 1);
      }
      else
      {
        $this->log("skipping ({$pkg_config['period']})", 1);
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

      $this->log("archiving..", 1);
      $filename = $this->prepareFilename('mysql-' . $row['Database'], 'sql.bz2');
      `mysqldump -u {$cfg['username']} -p{$cfg['password']} -h {$cfg['hostname']} {$row['Database']} | bzip2 -c > "{$filename}"`;
      $this->trigger('file', array('filename' => $filename));
      $this->log(" ..done", 1);

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

      $this->log("archiving..", 1);
      $filename = $this->prepareFilename('postgres-' . $db, 'sql.bz2');
      `su - {$cfg['sh_user']} -c "pg_dump -c --column-inserts --inserts {$db}" | bzip2 -c > "{$filename}"`;
      $this->trigger('file', array('filename' => $filename));
      $this->log(" ..done", 1);

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

  protected function backupCleanup($config)
  {
    $dirs = `ls {$this->config['storage']}`;
    foreach(explode("\n", $dirs) as $dir)
    {
      if(empty($dir)) continue;

      if(!$this->checkDateDirectory($dir))
      {
        $this->log("{$dir} expired");

        if(!$this->trigger('cleanup.before', array('directory' => $dir)))
        { // Error while deleting
          continue;
        }

        `rm -f {$this->config['storage']}{$dir}/*`;
        `rmdir {$this->config['storage']}{$dir}/`;

        $this->trigger('cleanup.after', array('directory' => $dir));

        $this->log($dir . ' deleted');
      }
      else
      {
        $this->log("{$dir} actual");
      }
    }
    $this->trigger('cleanup');
  }

  public function checkDateDirectory($dir)
  {
    $date = strtotime($dir);
    $period = $this->getDatePeriod($date);

    if(time() > strtotime('+' . $this->config['cleanup'][$period], $date))
    {
      return false;
    }
    else
    {
      return true;
    }
  }

  protected function getDatePeriod($date)
  { // TODO: Issue #2
    if($date == 'initial')
    {
      return "monthly";
    }
    elseif($date == strtotime('first monday ' . date('M Y', $date)))
    { // monthly
      return "monthly";
    }
    elseif(date('N', $date) == 1)
    { // weekly
      return "weekly";
    }
    else
    { // weekly
      return "daily";
    }
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