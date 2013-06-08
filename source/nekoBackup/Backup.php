<?php
namespace nekoBackup;

use \Symfony\Component\Yaml\Yaml;

use nekoBackup\Event\Action as ActionEvent;

class Backup
{
  protected static $instance;

  /**
   * @static
   * @param string $config
   * @return Backup
   */
  public static function get($config = '')
  {
    if(empty(self::$instance)) {
      self::$instance = new Backup($config);
    }
    return self::$instance;
  }

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

    foreach($this->config['schedule'] as $section) {
      BackupLogger::indent($section);
      BackupLogger::append("started", 2);

      global $dispatcher;
      $dispatcher->dispatch('nekobackup.action', new ActionEvent($section, $period));

      BackupLogger::append("finished", 2);
      BackupLogger::back();
    }

    BackupLogger::append("Backup finished.", 2);

    return true;
  }

  public static function getDatePeriod($date)
  { // TODO: Issue #2
    if($date == 'initial') {
      return "monthly";
    } elseif($date == strtotime('first monday ' . date('M Y', $date))) {
      return "monthly";
    } elseif(date('N', $date) == 1) {
      return "weekly";
    } else {
      return "daily";
    }
  }
}