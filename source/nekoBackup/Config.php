<?php
namespace nekoBackup;

use Symfony\Component\Yaml\Yaml;

class Config
{
  protected static $cached;

  public static function get($file)
  {
    if(empty(self::$cached[$file])) {
      $configPath = CONFIG_PATH . $file . '.yaml';

      if(!is_file($configPath)) {
        Logger::append('File "' . $configPath . '" not found');
        die(1);
      }

      self::$cached[$file] = Yaml::parse($configPath);
    }
    return self::$cached[$file];
  }
}
