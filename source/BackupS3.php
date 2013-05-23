<?php
class BackupS3
{
  protected static $config;

  public static function init($config_path)
  {
    if(!is_file($config_path))
    {
      BackupLogger::append('File "' . $config_path . '" not found');
      die(1);
    }

    if(!is_file(S3CMD_PATH . 's3cmd'))
    {
      BackupLogger::append('File "' . S3CMD_PATH . 's3cmd" not found...');

      if(is_file(BUNDLE_PATH . 's3cmd-1.5.0a3-mini.tar.gz'))
      {
        BackupLogger::append(' ..installing..');
        if(!is_dir(S3CMD_PATH)) mkdir(S3CMD_PATH);
        exec("tar -xzf " . BUNDLE_PATH . "s3cmd-1.5.0a3-mini.tar.gz -C " . S3CMD_PATH);

        if(!is_file(S3CMD_PATH . 's3cmd'))
        {
          BackupLogger::append(' ..failed!');
          die(1);
        }
        else
        {
          BackupLogger::append(' ..done!');
        }
      }
      else
      {
        BackupLogger::append(' ..cannot install!');
        die(1);
      }
    }

    self::$config = Spyc::YAMLLoad($config_path);
    self::$config['directory'] = "s3://" . self::$config['bucket'] . "/" . self::$config['directory'] . "/";

    if(!is_file(CONFIG_PATH . 's3cfg'))
    {
      self::prepareConfig();
    }

    if(!is_file(CONFIG_PATH . 's3cfg'))
    {
      BackupLogger::append('File "' . CONFIG_PATH . 's3cfg" not found');
      die(1);
    }

    BackupEvents::bind('file', array('BackupS3', 'uploadFile'));
    BackupEvents::bind('cleanup', array('BackupS3', 'cleanup'));
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
    $path   = S3CMD_PATH  . 's3cmd';
    $config = CONFIG_PATH . 's3cfg';

    if($action == 'put' || $action == 'del')
    {
      $params = '--recursive ' . $params;
    }

    if($action == 'put')
    {
      $params = '--multipart-chunk-size-mb=50 ' . $params;
    }

    $code = 0;
    exec("$path $action --config={$config} --no-progress {$params}", $output, $code);
    return !$code;
  }

  public static function prepareConfig()
  {
    $config_path = CONFIG_PATH . 's3cfg';

    $config = "";
    $config .= "[default]\n";
    $config .= "access_key = " . self::$config['access_key'] . "\n";
    $config .= "secret_key = " . self::$config['secret_key'] . "\n";
    $config .= "bucket_location = " . self::$config['location'] . "\n";
    $config .= "cloudfront_host = cloudfront.amazonaws.com\n";
    $config .= "default_mime_type = binary/octet-stream\n";
    $config .= "delete_removed = False\n";
    $config .= "dry_run = False\n";
    $config .= "enable_multipart = True\n";
    $config .= "encoding = UTF-8\n";
    $config .= "encrypt = False\n";
    $config .= "follow_symlinks = False\n";
    $config .= "force = False\n";
    $config .= "get_continue = False\n";
    $config .= "gpg_command = /usr/bin/gpg\n";
    $config .= "gpg_decrypt = %(gpg_command)s -d --verbose --no-use-agent --batch --yes --passphrase-fd %(passphrase_fd)s -o %(output_file)s %(input_file)s\n";
    $config .= "gpg_encrypt = %(gpg_command)s -c --verbose --no-use-agent --batch --yes --passphrase-fd %(passphrase_fd)s -o %(output_file)s %(input_file)s\n";
    $config .= "gpg_passphrase =\n";
    $config .= "guess_mime_type = True\n";
    $config .= "host_base = s3.amazonaws.com\n";
    $config .= "host_bucket = %(bucket)s.s3.amazonaws.com\n";
    $config .= "human_readable_sizes = False\n";
    $config .= "invalidate_on_cf = False\n";
    $config .= "list_md5 = False\n";
    $config .= "log_target_prefix =\n";
    $config .= "mime_type =\n";
    $config .= "multipart_chunk_size_mb = 15\n";
    $config .= "preserve_attrs = True\n";
    $config .= "progress_meter = True\n";
    $config .= "proxy_host =\n";
    $config .= "proxy_port = 0\n";
    $config .= "recursive = False\n";
    $config .= "recv_chunk = 4096\n";
    $config .= "reduced_redundancy = False\n";
    $config .= "send_chunk = 4096\n";
    $config .= "simpledb_host = sdb.amazonaws.com\n";
    $config .= "skip_existing = False\n";
    $config .= "socket_timeout = 300\n";
    $config .= "urlencoding_mode = normal\n";
    $config .= "use_https = False\n";
    $config .= "verbosity = WARNING\n";
    $config .= "website_endpoint = http://%(bucket)s.s3-website-%(location)s.amazonaws.com/\n";
    $config .= "website_error =\n";
    $config .= "website_index = index.html\n";

    file_put_contents($config_path, $config);
    chmod($config_path, 0600);
  }
}

