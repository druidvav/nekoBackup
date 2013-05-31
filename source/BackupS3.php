<?php
use Aws\S3\S3Client;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use \Symfony\Component\Yaml\Yaml;

class BackupS3
{
  protected static $config;

  /**
   * @var S3Client
   */
  protected static $client;

  public static function init($config_path)
  {
    if(!is_file($config_path))
    {
      BackupLogger::append('File "' . $config_path . '" not found');
      die(1);
    }

    self::$config = Yaml::parse($config_path);
    self::$config['directory'] = self::$config['directory'] . "/";

    self::$client = S3Client::factory(array(
      'key'    => self::$config['access_key'],
      'secret' => self::$config['secret_key']
    ));

    BackupEvents::bind('file', array('BackupS3', 'uploadFile'));
    BackupEvents::bind('cleanup', array('BackupS3', 'cleanup'));
  }

  public static function uploadFile($options, $retries = 3)
  {
    $storage_dir = Backup::get()->config['storage'];
    $remote_file = self::$config['directory'] . str_replace($storage_dir, '', $options['filename']);

    $uploader = UploadBuilder::newInstance()
      ->setClient(self::$client)
      ->setSource($options['filename'])
      ->setBucket(self::$config['bucket'])
      ->setKey($remote_file)
      ->setConcurrency(4)
      ->setMinPartSize(50*1024*1024) // 50 Mb
      ->build();

    BackupLogger::append('uploading file..', 1);

    try {
      $uploader->upload();
      BackupLogger::append('complete!', 1);
      BackupLogger::append('removing local file..', 1);
      unlink($options['filename']);
    } catch (MultipartUploadException $e) {
      if($retries > 0) {
        BackupLogger::append('failed: ' . $e->getMessage(), 1);
        BackupLogger::append('retrying...', 1);
        return self::uploadFile($options, $retries - 1);
      } else {
        $uploader->abort();
        BackupLogger::append('failed: ' . $e->getMessage(), 1);
        exit;
      }
    }

    return true;
  }

  public static function cleanup($options)
  {
    $iterator = self::$client->getIterator('ListObjects', array(
      'Bucket' => self::$config['bucket'],
      'Prefix' => self::$config['directory']
    ));

    foreach($iterator as $object)
    {
      if($object['Size'] != 0)
      { // Не директория
        continue;
      }

      $dir = basename(trim($object['Key']));

      if(!BackupCleanup::checkDateDirectory($dir))
      {
        $objects = self::$client->listObjects(array(
          'Bucket' => self::$config['bucket'],
          'Prefix' => self::$config['directory'] . $dir . '/'
        ));

        $result = self::$client->deleteObjects(array(
          'Bucket' => self::$config['bucket'],
          'Objects' => $objects['Contents']
        ));

        if(sizeof($objects['Contents']) == sizeof($result['Deleted']))
        {
          BackupLogger::append('s3 > ' . $dir . ' > removed');
        }
        else
        {
          BackupLogger::append('s3 > ' . $dir . ' > error while removing!');
        }
      }
      else
      {
        BackupLogger::append('s3 > ' . $dir . ' > actual');
      }
    }
  }
}
