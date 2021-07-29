<?php
namespace Reviews\Application\Reviews;
class Shortcode
{
	public static function init()
	{
		add_action('init', array(__NAMESPACE__ .'\Shortcode', 'boot'));
	}
	public static function boot()
	{
		static::add('reviews-form', 'FeedbackController@showForm');		
		static::add('reviews-slider', 'FeedbackController@slider');
		static::add('reviews-average', 'FeedbackController@average');
		
	}
	public static function add($tag, $handler)
	{
		$namespace = 'Reviews\Application\Reviews\Controllers';
		list($class, $method) = explode('@', $handler);
		add_shortcode($tag, array("$namespace\\{$class}", $method));
	}
}
Shortcode::boot();
