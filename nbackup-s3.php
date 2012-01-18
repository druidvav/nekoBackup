<?php
include_once(dirname(__FILE__) . '/lib/Backup.php');
include_once(dirname(__FILE__) . '/lib/yaml.php');

echo "  === nekoBackup 1.0beta1 [Amazon S3] === \n";
echo "\n";

if(!is_file(dirname(__FILE__) . '/cfg/s3.yaml'))
{
  die('File "cfg/s3.yaml" must be present');
}

if(!is_file(dirname(__FILE__) . '/cfg/s3cfg'))
{
  die('File "cfg/s3cfg" must be present');
}

$config = Spyc::YAMLLoad(dirname(__FILE__) . '/cfg/s3.yaml');
$config['directory'] = "s3://{$config['bucket']}/{$config['directory']}/";

$backup = new Backup(Spyc::YAMLLoad(dirname(__FILE__) . '/cfg/config.yaml'));
$backup->bind('file', 'file_callback');
$backup->bind('cleanup', 'cleanup_callback');
$backup->execute(@$_SERVER['argv'][1] == 'initial' ? 'initial' : time());

function file_callback(Backup &$self, $options)
{
  global $config;

  $remote_file = $config['directory'] . str_replace($self->config['storage'], '', $options['filename']);
  $remote_dir = dirname($remote_file) . '/';

  $self->log('uploading file..', 1);

  if(run_s3cmd('put', "{$options['filename']} {$remote_file}", $output))
  {
    $self->log('removing local file..', 1);
    unlink($options['filename']);
  }
  else
  {
    $self->log('failed', 1);
  }

  return true;
}

function cleanup_callback(Backup &$self, $options)
{
  global $config;

  run_s3cmd('ls', $config['directory'], $output);
  foreach($output as $line)
  {
    $line = explode(' DIR ', $line, 2);
    if(sizeof($line) != 2) continue;

    $dir = basename(trim($line[1]));

    if(!$self->checkDateDirectory($dir))
    {
      if(!run_s3cmd('del', $config['directory'] . $dir . '/', $_tmp))
      {
        $self->log('s3 > ' . $dir . ' > error while removing!');
      }
      else
      {
        $self->log('s3 > ' . $dir . ' > removed');
      }
    }
  }
}

function run_s3cmd($action, $params, &$output)
{
  $path = dirname(__FILE__) . '/s3/s3cmd';
  $config = dirname(__FILE__) . '/cfg/s3cfg';

  if($action == 'put' || $action == 'del')
  {
    $params = '--recursive ' . $params;
  }

  $code = 0;
  exec("$path $action --config={$config} --no-progress {$params}", $output, $code);
  return !$code;
}