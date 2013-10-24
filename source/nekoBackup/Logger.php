<?php
namespace nekoBackup;

class Logger
{
  protected static $log_indent = array();

  // Levels: 1 - notice, 2 - status update, 3 - warning, 4 - error
  public static function append($message, $level = 1)
  {
    $date = date("Y.m.d H:i:s");
    $indent = self::$log_indent ? implode(' > ', self::$log_indent) . ' > ' : '';
    $message = "$date > {$indent}$message \n";

    echo $message;
    self::appendFile($message);
  }

  public static function appendFile($message)
  {
    $fh = fopen(LOG_PATH, 'a+') or die('Couldn\'t open logfile');
    if(flock($fh, LOCK_EX))
    {
      fseek($fh, 0, SEEK_END);
      fwrite($fh, $message);
    }
    else
    {
      die('Couldn\'t block logfile for exclusive access');
    }
    fclose($fh);
    @chmod(LOG_PATH, 0666);
  }


  public static function indent($group) { self::$log_indent[] = $group; }
  public static function back() { array_pop(self::$log_indent); }
}