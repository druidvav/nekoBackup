<?php
include_once('./lib/Backup.php');
include_once('./lib/yaml.php');

$config = Spyc::YAMLLoad('config.yaml');

$backup = new Backup($config);
$backup->run(strtotime('this monday'));