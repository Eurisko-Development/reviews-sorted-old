<?php
class ReviewsAutoloader
{
	protected $pluginNamespace;
	public function __construct($pluginNamespace) 
	{	$this->pluginNamespace = $pluginNamespace;
		spl_autoload_register(array($this, 'loader'));
	}
	public function loader($className) 
	{
		// Corrections
		$className = str_replace('\\', '/', $className);

		// Load plugin classes
		if (preg_match("/^Reviews\/Application\/{$this->pluginNamespace}/", $className)) {
			$className = str_replace("Reviews/Application/{$this->pluginNamespace}/", "", $className);
			$file = __DIR__ .'/app/'. $className . '.php';
		}
		if (!isset($file)) {
			$file = __DIR__ .'/vendor/'. $className .'.php';
		}
		if (file_exists($file)) include $file;
	}
}