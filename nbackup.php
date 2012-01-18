<?php
include_once('./lib/Backup.php');
include_once('./lib/yaml.php');

$backup = new Backup(Spyc::YAMLLoad('./cfg/config.yaml'));
$backup->execute(time());