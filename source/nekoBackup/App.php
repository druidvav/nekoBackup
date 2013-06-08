<?php
namespace nekoBackup;

use nekoBackup\Config;
use nekoBackup\Event\Action as ActionEvent;
use nekoBackup\S3Driver\Driver as S3Driver;
use nekoBackup\BasicDriver\Driver as BasicDriver;
use Symfony\Component\EventDispatcher\EventDispatcher;

class App
{
  protected $isInitial = false;
  protected $dispatcher;

  public function __construct($driver)
  {
    $this->dispatcher = new EventDispatcher();

    new BasicDriver($this->dispatcher);

    if($driver == 's3') {
      Logger::append('Using [Amazon S3] storage driver.');
      new S3Driver($this->dispatcher);
    }
  }

  public function setIsInitial($value)
  {
    $this->isInitial = $value;
  }

  public function bootstrap()
  {
    if($this->isInitial) {
      Logger::append('Running [initial] backup mode.');
    }

    $this->execute($this->isInitial ? 'initial' : time());
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
    $config = Config::get('schedule');

    Logger::append("Backup started: {$period}.", 2);

    foreach($config['schedule'] as $section) {
      Logger::indent($section);
      Logger::append("started", 2);

      $this->dispatcher->dispatch('nekobackup.action', new ActionEvent($section, $period));

      Logger::append("finished", 2);
      Logger::back();
    }

    Logger::append("Backup finished.", 2);

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