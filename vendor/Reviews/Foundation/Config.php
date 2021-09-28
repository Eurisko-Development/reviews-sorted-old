<?php

namespace Reviews\Foundation;

class Config
{
	public static $configs = [];

	public static function set($name, $file) 
	{
		static::$configs[$name] = require $file;
	}

	public static function get($key) 
	{
		list($name, $key) = explode('.', $key);

		return static::$configs[$name][$key];
	}
}