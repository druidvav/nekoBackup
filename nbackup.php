<?php
include_once(dirname(__FILE__) . '/lib/Backup.php');

echo "nekoBackup 1.1alpha2\n";
echo "\n";

$backup = new Backup(dirname(__FILE__) . '/cfg/config.yaml');
$backup->execute(@$_SERVER['argv'][1] == 'initial' ? 'initial' : time());