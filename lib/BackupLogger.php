<?php
class BackupLogger
{
  protected static $log_indent = array();

  // Levels: 1 - notice, 2 - status update, 3 - warning, 4 - error
  public static function append($message, $level = 1)
  {
    $date = date("Y.m.d H:i:s");
    $indent = self::$log_indent ? implode(' > ', self::$log_indent) . ' > ' : '';
    echo "$date > $level > {$indent}$message \n";
  }

  public static function indent($group) { self::$log_indent[] = $group; }
  public static function back() { array_pop(self::$log_indent); }
}