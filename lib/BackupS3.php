<?php
class BackupS3
{
  protected static $config;

  public static function init($config_path)
  {
    BackupEvents::bind('file', array('BackupS3', 'uploadFile'));
    BackupEvents::bind('cleanup', array('BackupS3', 'cleanup'));

    self::$config = Spyc::YAMLLoad($config_path);
    self::$config['directory'] = "s3://" . self::$config['bucket'] . "/" . self::$config['directory'] . "/";
  }

  public static function uploadFile($options)
  {
    $storage_dir = Backup::get()->config['storage'];
    $remote_file = self::$config['directory'] . str_replace($storage_dir, '', $options['filename']);
    $remote_dir = dirname($remote_file) . '/';

    BackupLogger::append('uploading file..', 1);

    if(self::s3cmd('put', "{$options['filename']} {$remote_file}", $output))
    {
      BackupLogger::append('removing local file..', 1);
      unlink($options['filename']);
    }
    else
    {
      BackupLogger::append('failed', 1);
    }

    return true;
  }

  public static function cleanup($options)
  {
    self::s3cmd('ls', self::$config['directory'], $output);
    foreach($output as $line)
    {
      $line = explode(' DIR ', $line, 2);
      if(sizeof($line) != 2) continue;

      $dir = basename(trim($line[1]));

      if(!BackupCleanup::checkDateDirectory($dir))
      {
        if(!self::s3cmd('del', self::$config['directory'] . $dir . '/', $_tmp))
        {
          BackupLogger::append('s3 > ' . $dir . ' > error while removing!');
        }
        else
        {
          BackupLogger::append('s3 > ' . $dir . ' > removed');
        }
      }
      else
      {
        BackupLogger::append('s3 > ' . $dir . ' > actual');
      }
    }
  }

  public static function s3cmd($action, $params, &$output)
  {
    $path = dirname(__FILE__) . '/../s3/s3cmd';
    $config = dirname(__FILE__) . '/../cfg/s3cfg';

    if($action == 'put' || $action == 'del')
    {
      $params = '--recursive ' . $params;
    }

    $code = 0;
    exec("$path $action --config={$config} --no-progress {$params}", $output, $code);
    return !$code;
  }
}