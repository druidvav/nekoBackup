<?php
class Backup
{
  public $config;
  public $period;
  protected $callbacks;

  public function __construct($config_path)
  {
    spl_autoload_register(array('Backup', 'Autoload'));

    $this->config = Spyc::YAMLLoad($config_path);
  }

  public static function Autoload($class)
  {
    if(file_exists(dirname(__FILE__) . '/' . $class . '.php'))
    {
      include_once(dirname(__FILE__) . '/' . $class . '.php');
    }
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
    $this->period = $period;

    BackupLogger::append("Backup started: {$period}.", 2);

    $this->trigger('start');
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
      $class::execute($this, $this->config[$section]);

      BackupLogger::append("finished", 2);
      BackupLogger::back();
    }
    $this->trigger('finish');

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

    //
    if(is_file($filename))
    {
      return $this->prepareFilename($name, $ext, $postfix + 1);
    }

    return $filename;
  }

  public function getDatePeriod($date)
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