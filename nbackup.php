<?php
include_once(dirname(__FILE__) . '/lib/Backup.php');
include_once(dirname(__FILE__) . '/lib/BackupDirectory.php');
include_once(dirname(__FILE__) . '/lib/BackupSubdirectory.php');
include_once(dirname(__FILE__) . '/lib/yaml.php');

echo "nekoBackup 1.1alpha1\n";
echo "\n";

$backup = new Backup(Spyc::YAMLLoad(dirname(__FILE__) . '/cfg/config.yaml'));
$backup->execute(@$_SERVER['argv'][1] == 'initial' ? 'initial' : time());