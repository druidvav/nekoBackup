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
    $app->checkPid('backup');
    $app->archive();
    $app->cleanup();
    $app->removePidFile('backup');
  });
$console->register('upload')
  ->setDescription('Upload files to Amazon S3')
  ->setCode(function () use ($app) {
    $app->checkPid('s3-upload');
    $app->uploadAmazonS3();
    $app->removePidFile('s3-upload');
  });
$console->register('upload-cleanup')
  ->setDescription('Cleanup files in Amazon S3')
  ->setCode(function () use ($app) {
    $app->checkPid('s3-cleanup');
    $app->cleanupAmazonS3();
    $app->removePidFile('s3-cleanup');
  });
$console->register('install')
  ->setDescription('Install backup script to crontab')
  ->setCode(function (InputInterface $input, OutputInterface $output) {
    $output->write("Installing crontab...");
    $lines  = "30  1 * * * " . EXECUTABLE . " backup &> /dev/null\n";
    $lines .= " 0  * * * * " . EXECUTABLE . " upload &> /dev/null\n";
    $lines .= "00 23 * * * " . EXECUTABLE . " upload-cleanup &> /dev/null\n";
    exec("(crontab -l; echo \"{$lines}\") | crontab -");
    $output->writeln(" done.");
  });
$console->run();