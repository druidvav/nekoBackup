<?php
include_once('./lib/Backup.php');
include_once('./lib/yaml.php');
include_once('./lib/Dropbox/autoload.php');

echo "nekoBackup dropbox 1.0beta2\n";
echo "\n";

if(!is_file('./cfg/dropbox.json'))
{
  die('File "cfg/dropbox.json" must be present');
}

$config = json_decode(file_get_contents('./cfg/dropbox.json'), true);

if(empty($config['token']) && @$_SERVER['argv'][1] != 'install')
{
  die('Run "php ' . basename(__FILE__) . ' install" to prepare dropbox for first launch.' . "\n");
}

if(@$_SERVER['argv'][1] == 'install')
{
  while(empty($config['token']))
  {
    $oauth = new Dropbox_OAuth_Curl($config['key'], $config['secret']);

    $tokens = $oauth->getRequestToken();
    echo "Please open this link in browser to enable dropbox:\n";
    echo $oauth->getAuthorizeUrl() . "\n";
    echo "\nPress ENTER to continue...\n";
    fgets(STDIN);

    try
    {
      $tokens = $oauth->getAccessToken();
    }
    catch(Exception $e)
    {
      $tokens = array();
    }

    if(!empty($tokens['token']))
    {
      $config['token'] = $tokens['token'];
      $config['token_secret'] = $tokens['token_secret'];

      echo "Got your token! Trying to save...";
      if(file_put_contents('./cfg/dropbox.json', json_encode($config)))
      {
        echo " done.\n";
      }
      else
      {
        die(" Error!\n");
      }
    }
    else
    {
      echo "You must allow access to your dropbox account! Try again.\n";
    }
  }
  exit;
}

$oauth = new Dropbox_OAuth_Curl($config['key'], $config['secret']);
$oauth->setToken($config);

$dropbox = new Dropbox_API($oauth);
$result = $dropbox->getAccountInfo();

if(empty($result['uid']))
{
  echo "Error! Can't connect to dropbox!";
  exit;
}

echo "Hello, {$result['display_name']}.\n";

try {
  $response = $dropbox->getMetaData('nekoBackup');
} catch (Dropbox_Exception_NotFound $e) {
  $response = $dropbox->createFolder('nekoBackup');
  $response = $dropbox->getMetaData('nekoBackup');
}

echo "Dropbox folder 'nekoBackup' ready!\n";

echo "Starting backup process\n\n";

$backup = new Backup(Spyc::YAMLLoad('./cfg/config.yaml'));
$backup->bind('file', 'file_callback');
$backup->bind('cleanup', 'cleanup_callback');
$backup->execute(@$_SERVER['argv'][1] == 'initial' ? 'initial' : time());

function file_callback(Backup &$self, $options)
{
  global $dropbox;

  $remote_file = 'nekoBackup/' . str_replace($self->config['storage'], '', $options['filename']);
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

  return true;
}

function cleanup_callback(Backup &$self, $options)
{
  global $dropbox;

  $result = $dropbox->getMetaData('nekoBackup', true);
  foreach($result['contents'] as $content)
  {
    if(empty($content['is_dir'])) continue;

    $dir = basename($content['path']);

    if(!$self->checkDateDirectory($dir))
    {
      if(!$dropbox->delete($content['path']))
      {
        $self->log('dropbox > ' . $content['path'] . ' > error while removing!');
      }
      else
      {
        $self->log('dropbox > ' . $content['path'] . ' > removed');
      }
    }
  }
}