<?php
namespace nekoBackup;

use Symfony\Component\Yaml\Yaml;

class Config
{
  public static function get($file)
  {
    $configPath = CONFIG_PATH . $file . '.yaml';

    if(!is_file($configPath)) {
      BackupLogger::append('File "' . $configPath . '" not found');
      die(1);
    }

    return Yaml::parse($configPath);
  }
}
