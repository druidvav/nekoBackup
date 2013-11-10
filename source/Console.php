<?php
use Symfony\Component\Console\Application;
//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$app = new nekoBackup\App();

$console = new Application();
$console->setName('[ nekoBackup by druidvav ]');
$console->setVersion(VERSION);
$console->register('backup')
  ->setDescription('Start backup process')
  ->setCode(function () use ($app) {
    $app->archive();
    $app->cleanup();
  });
$console->register('upload')
  ->setDescription('Upload files to  Amazon S3')
  ->setCode(function () use ($app) {
    $app->uploadAmazonS3();
  });
$console->register('install')
  ->setDescription('Install backup script to crontab')
  ->setCode(function (InputInterface $input, OutputInterface $output) {
    $output->write("Installing crontab...");
    $line = "30 1 * * * php " . EXECUTABLE . " backup &> /dev/null\n";
    exec("(crontab -l; echo \"{$line}\") | crontab -");
    $output->writeln(" done.");
  });
$console->run();