<?php
class BackupEvents
{
  protected static $callbacks;

  public static function bind($event, $callback)
  {
    self::$callbacks[$event] = $callback;
  }

  public static function trigger($event, $options = array())
  {
    if(empty(self::$callbacks[$event])) return true;
    return call_user_func_array(self::$callbacks[$event], array($options));
  }
}