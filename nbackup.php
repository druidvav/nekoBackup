<?php
define('CONFIG_PATH', dirname(__FILE__) . '/config/');
define('SOURCE_PATH', dirname(__FILE__) . '/source/');
define('VENDOR_PATH', dirname(__FILE__) . '/vendor/');
define('LOG_PATH',    '/var/log/nbackup.log');

include_once(VENDOR_PATH . 'autoload.php');

echo "  >>  nekoBackup 1.3alpha by druidvav  << \n";
echo "\n";

$opts = getopt('', array('driver:', 'initial', 'install'));

if(isset($opts['install']))
{
  echo "Installing crontab...";

  $line = "0 3 * * * php " . __FILE__ . " --driver={$opts['driver']} &> /dev/null\n";
  `(crontab -l; echo "{$line}") | crontab -`;

  echo " done.\n";
  exit;
}

$app = new \nekoBackup\App(@$opts['driver']);
if(!empty($opts['initial'])) {
  $app->setIsInitial(true);
}
$app->bootstrap();