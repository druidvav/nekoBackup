<?php
include_once('./lib/Backup.php');
include_once('./lib/yaml.php');
include_once('./lib/Dropbox/autoload.php');

$config = Spyc::YAMLLoad('config.yaml');

$oauth = new Dropbox_OAuth_PEAR($config['dropbox']['key'], $config['dropbox']['secret']);

if(empty($config['dropbox']['token']))
{
  $tokens = $oauth->getRequestToken();
  echo "Please visit this link to enable dropbox:\n";
  echo $oauth->getAuthorizeUrl() . "\n\n";
  fgets(STDIN);

  $tokens = $oauth->getAccessToken();
  print_r($tokens);

  echo "Save this tokens to dropbox config.\n";
  exit;
}

$oauth->setToken($config['dropbox']);

$dropbox = new Dropbox_API($oauth);
$result = $dropbox->getAccountInfo();

if(empty($result['uid']))
{
  echo "Error! Can't connect to dropbox!";
  exit;
}

print_r($result);

try {
  $response = $dropbox->getMetaData('nekoBackup');
} catch (Dropbox_Exception_NotFound $e) {
  $response = $dropbox->createFolder('nekoBackup');
  $response = $dropbox->getMetaData('nekoBackup');
}

function file_callback(Backup &$self, $options)
{
  global $dropbox, $config;

  $remote_file = 'nekoBackup/' . str_replace($config['storage'], '', $options['filename']);
  $remote_dir = dirname($remote_file) . '/';

  try {
    $response = $dropbox->getMetaData($remote_dir);
  } catch (Dropbox_Exception_NotFound $e) {
    $self->log('creating directory in dropbox..', 1);
    $response = $dropbox->createFolder($remote_dir);
    $response = $dropbox->getMetaData($remote_dir);
  }

  $self->log('uploading file..', 1);

  $response = $dropbox->putFile($remote_file, $options['filename']);

  if($response == '1')
  {
    $self->log('removing local file..', 1);
    unlink($options['filename']);
  }

  $self->log(' ..done', 1);

  return true;
}

// TODO space usage monitor
// TODO Cleanup

$backup = new Backup($config);
$backup->bind('file', 'file_callback');
$backup->run(strtotime('this monday'));

