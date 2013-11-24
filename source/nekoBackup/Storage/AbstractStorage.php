<?php
namespace nekoBackup\Storage;

use nekoBackup\Config;
use Symfony\Component\Finder\Finder;

class AbstractStorage
{
  /* @var Config */
  protected $config;

  public function __construct(Config $config)
  {
    $this->config = $config;
  }

  protected function getFinder()
  {
    return new Finder();
  }

  protected function getStorageFinder()
  {
    return $this->getFinder()->in($this->config->get('storage'))->sortByName();
  }
}