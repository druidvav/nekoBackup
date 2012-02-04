<?php
spl_autoload_register(array('Backup', 'Autoload'));

class Backup
{
  // Static zone

  protected static $instance;

  /**
   * @static
   * @param string $config_path
   * @return Backup
   */
  public static function get($config_path = '')
  {
    if(empty(self::$instance))
    {
      self::$instance = new Backup(Spyc::YAMLLoad($config_path));
    }
    return self::$instance;
  }

  public static function Autoload($class)
  {
    if(file_exists(dirname(__FILE__) . '/' . $class . '.php'))
    {
      include_once(dirname(__FILE__) . '/' . $class . '.php');
    }
  }

  // Object zone

  public $config;

  public function __construct($config)
  {
    $this->config = $config;
  }

  public function execute($date)
  {
    switch($period = self::getDatePeriod($date))
    { // TODO: Issue #2
      case 'monthly': $this->executePeriodic('monthly');
      case 'weekly':  $this->executePeriodic('weekly');
      case 'daily':   $this->executePeriodic('daily'); break;
    }
  }

  public function executePeriodic($period)
  {
    BackupLogger::append("Backup started: {$period}.", 2);
    BackupEvents::trigger('start');

    foreach($this->config['schedule'] as $section)
    {
      if(empty($this->config[$section]))
      {
        BackupLogger::append("Unknown section '{$section}'", 3);
        continue;
      }

      BackupLogger::indent($section);
      BackupLogger::append("started", 2);

      $class = 'Backup' . ucfirst($section);
      $class::execute($this->config[$section], $period);

      BackupLogger::append("finished", 2);
      BackupLogger::back();
    }

    BackupEvents::trigger('finish');
    BackupLogger::append("Backup finished.", 2);

    return true;
  }

  public function prepareFilename($name, $ext = '', $postfix = '')
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

    if(is_file($filename))
    {
      return $this->prepareFilename($name, $ext, $postfix + 1);
    }

    return $filename;
  }

  public static function getDatePeriod($date)
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
}