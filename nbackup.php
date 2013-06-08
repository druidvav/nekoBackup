<?php
define('CONFIG_PATH', dirname(__FILE__) . '/config/');
define('SOURCE_PATH', dirname(__FILE__) . '/source/');
define('VENDOR_PATH', dirname(__FILE__) . '/vendor/');
define('LOG_PATH',    '/var/log/nbackup.log');

include_once(VENDOR_PATH . 'autoload.php');

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application();
$console->setName('[ nekoBackup by druidvav ]');
$console->setVersion('1.3rc');
$console->register('backup')
  ->setDefinition(array(
    new InputArgument('driver', InputArgument::OPTIONAL, 'Backup driver [basic, s3]', 'basic'),
    new InputOption('initial', 'i', InputOption::VALUE_OPTIONAL, 'Run the first backup with this parameter set to 1', 0),
  ))
  ->setDescription('Starts backup process')
  ->setCode(function (InputInterface $input, OutputInterface $output) {
    $app = new nekoBackup\App($input->getArgument('driver'));
    if($input->getOption('initial')) {
      $app->setIsInitial(true);
    }
    $app->bootstrap();
  });
$console->register('install')
  ->setDefinition(array(
    new InputArgument('driver', InputArgument::OPTIONAL, 'Backup driver [basic, s3]', 'basic'),
  ))
  ->setDescription('Install backup script to crontab')
  ->setCode(function (InputInterface $input, OutputInterface $output) {
    $driver = $input->getArgument('driver');

    $output->write("Installing crontab...");
    $line = "30 1 * * * php " . __FILE__ . " backup {$driver} &> /dev/null\n";
    exec("(crontab -l; echo \"{$line}\") | crontab -");
    $output->writeln(" done.");
  });
$console->run();