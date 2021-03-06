<?php
namespace Reviews\Foundation;
use Reviews\Foundation\View;
class Route
{
	public static $app;
	public static $pluginDir;
	public static $routes = [
		'GET' => [],
		'POST' => [],
		'PUT' => [],
		'PATCH' => [],
		'DELETE' => []
	];
	public static function get($path, $handler) 
	{
		static::$routes['GET'][$path] = $handler;
	}
	public static function post($path, $handler) 
	{
		static::$routes['POST'][$path] = $handler;
	}
	public static function put($path, $handler) 
	{
		static::$routes['PUT'][$path] = $handler;
	}
	public static function patch($path, $handler) 
	{
		static::$routes['PATCH'][$path] = $handler;
	}
	public static function delete($path, $handler) 
	{
		static::$routes['DELETE'][$path] = $handler;
	}
	public static function init($pluginDir, $app) 
	{
		static::$pluginDir = $pluginDir;
		static::$app = $app;
		add_action('init', [__CLASS__, 'endpoint'], 2);
		add_action('template_include', [__CLASS__, 'template']);
		add_action('parse_request', [__CLASS__, 'sniff']);
		//add_filter('page_template', [__CLASS__, 'template']);
	}
	public static function endpoint() 
	{
		
	}
	public static function sniff() 
	{
		if ( !empty(static::$routes[$_SERVER['REQUEST_METHOD']]) ) {
			foreach (static::$routes[$_SERVER['REQUEST_METHOD']] as $route => $handler) {
				$quotedRoute = str_replace("/", "\/", "/Reviews/". $route);
				if (preg_match("/{$quotedRoute}\/?/i", $_SERVER['REQUEST_URI'], $data)) {
					$namespace = "Reviews\Application\\". static::$app ."\Controllers";
					list($class, $method) = explode('@', $handler);
					call_user_func_array(["{$namespace}\\{$class}", $method], []);
				}
			}
		}
	}
	public static function template($page_template) 
	{
		if (preg_match("/\/Reviews/i", $_SERVER['REQUEST_URI'])) {
			$page_template = static::$pluginDir .'/app/views/templates/page.php';
		}
		
		return $page_template;
	}
}