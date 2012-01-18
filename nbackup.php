<?php
include_once('./lib/Backup.php');
include_once('./lib/yaml.php');

echo "nekoBackup 1.0beta1\n";
echo "\n";

$backup = new Backup(Spyc::YAMLLoad('./cfg/config.yaml'));
$backup->execute(@$_SERVER['argv'][1] == 'initial' ? 'initial' : time());