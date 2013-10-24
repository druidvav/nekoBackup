<?php
namespace nekoBackup;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Config
{
  protected $data;

  public function __construct()
  {
    $this->data = Yaml::parse(CONFIG_FILE);
    $this->data['sections'] = !empty($this->data['sections']) ? $this->data['sections'] : array();

    if(!empty($this->data['sections.include'])) {
      $finder = new Finder();
      $finder->files()->in(dirname(CONFIG_FILE) . '/' . $this->data['sections.include'])->depth(0)->name('*.yaml')->sortByName();
      foreach ($finder as $file) {
        /* @var SplFileInfo $file */
        $filename = $file->getRealpath();
        $this->data['sections'] = array_merge($this->data['sections'], Yaml::parse($filename));
      }
    }
  }

  public function get($name)
  {
    return $this->data[$name];
  }

  public function checkScheduleMatch($schedule)
  {
    if(is_array($schedule)) {
      if($schedule[0] == 'weekly' && $schedule[1] == date('w')) {
        return true;
      }
    } elseif($schedule == 'daily') {
      return true;
    }
    return false;
  }

  public function getLastMatchedDate($schedule)
  {
    if(is_array($schedule)) {
      if($schedule[0] == 'weekly') {
        $daysBack = ($schedule[1] - date('w') - 7) % 7;
        return new \DateTime($daysBack . ' days');
      }
    } elseif($schedule == 'daily') {
      return new \DateTime('yesterday');
    }
    return null;
  }

  public function checkIfArchiveExpired($filename)
  {
    list($date, $expireDays, $title, $ext) = explode('.', basename($filename), 4);
    return strtotime('+' . $expireDays . ' days', strtotime($date)) < time();
  }
}
