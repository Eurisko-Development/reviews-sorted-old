<?php

namespace Reviews\Application\Reviews;

class Assets

{	public static function boot()

	{

		wp_enqueue_style('Reviews-reviews', plugin_dir_url( __FILE__ ) . 'assets/css/main.css' );

		wp_enqueue_style('Reviews-reviews2', plugin_dir_url( __FILE__ ) . 'assets/css/style.php' );

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script('iris-min-js', plugin_dir_url( __FILE__ ) . 'assets/js/iris.min.js', array('jquery'), '',  true );

		wp_enqueue_script('wp-color-picker-alpha', plugin_dir_url( __FILE__ ) . 'assets/js/wp-color-picker-alpha.js',  array( 'wp-color-picker' ), '',  true );

		wp_enqueue_script('Reviews-reviews-custom-js', plugin_dir_url( __FILE__ ) . 'assets/js/custom.js', array('jquery'),        '',  true );	

		wp_enqueue_script('Reviews-reviews-js', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.bxslider.js', array('jquery'),'',  true );

	}

}

add_action('init', ['Reviews\Application\Reviews\Assets', 'boot']);

