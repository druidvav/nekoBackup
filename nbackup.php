<?php
include_once('./lib/Backup.php');
include_once('./lib/yaml.php');

$config = Spyc::YAMLLoad('config.yaml');

if(empty($_SERVER['argv'][1]))
{
  die('Invalid parameters');
}

$backup = new Backup($config);
$backup->run(strtotime('this monday'), $_SERVER['argv'][1]);