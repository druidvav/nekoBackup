<?php
namespace nekoBackup\Builder;

use nekoBackup\Config;

abstract class AbstractBuilder
{
  protected $title;
  protected $config;
  protected $section;
  protected $archives = array();

  public function __construct(Config $config, $title, $section)
  {
    $this->title = $title;
    $this->config = $config;
    $this->section = $section;
  }

  abstract public function build();

  public function getArchives()
  {
    $this->build();
    return $this->archives;
  }
}