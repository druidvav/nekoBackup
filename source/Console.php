<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application();
$console->setName('[ nekoBackup by druidvav ]');
$console->setVersion(VERSION);
$console->register('backup')
  ->setDefinition(array(
    new InputArgument('driver', InputArgument::OPTIONAL, 'Backup driver [basic, s3]', 'basic'),
  ))
  ->setDescription('Start backup process')
  ->setCode(function (InputInterface $input, OutputInterface $output) {
    $app = new nekoBackup\App($input->getArgument('driver'));
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