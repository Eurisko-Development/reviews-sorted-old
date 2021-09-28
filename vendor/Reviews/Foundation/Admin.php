<?php

namespace Reviews\Foundation;

class Admin
{
	public static $pages = [];

	public static function boot() 
	{
		foreach (static::$pages as $plugin => $page) { 
	   
			add_menu_page('','Reviews Sorted', $page['order'], $plugin);

			foreach ($page['pages'] as $name => $pageData) {
				static::addPage($plugin, $page['namespace'], $name, $plugin, $pageData);
			}
		}
	} 

	protected static function addPage($plugin, $namespace, $name, $parentSlug, $pageData)
	{
		$handlerNamespace = "Reviews\Application\\$namespace\Controllers";
		list($class, $handler) = explode('@', $pageData['uses']);

		if (!isset($pageData['capability'])) {
			$pageData['capability'] = 1;
		}

		add_submenu_page($plugin, $name, $name, $pageData['capability'], $parentSlug .'/'. $pageData['slug'], [$handlerNamespace .'\\'. $class, $handler]);

		if (isset($pageData['pages'])) {
			foreach ($pageData['pages'] as $page) {
				static::addPage('', $namespace, '', $parentSlug, $page);
			}
		}
	}

	public static function pages($plugin, $namespace, $name, $pages, $order = null) 
	{
		static::$pages[$plugin] = compact('namespace', 'name', 'pages', 'order');
	}
}