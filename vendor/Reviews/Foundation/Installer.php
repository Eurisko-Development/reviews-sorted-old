<?php

namespace Reviews\Foundation;

class Installer
{
	protected static $activationHooks;
    protected static $deactivationHooks;

	public static function init($file) 
	{
		register_activation_hook($file, array(__CLASS__, 'activate'));
        register_deactivation_hook( $file, array(__CLASS__, 'deactivate') );
	}

	public static function boot(\Closure $callable) 
	{
		static::$activationHooks[] = $callable;
	}


    public static function bootde(\Closure $callable)
    {
        static::$deactivationHooks[] = $callable;
    }

	public static function activate() 
	{
		
		
		$my_post = array(
		  'post_title'    => wp_strip_all_tags( 'Submit A Review ' ),
		  'post_content'  =>  '[reviews-form]',
		  'post_status'   => 'publish',
		  'post_author'   => 1,
		  'post_type'     => 'page',
		  'post_slug'     => 'submit-a-review',
		);

		// Insert the post into the database
	
	$newvalue = wp_insert_post( $my_post, false );
    update_option( 'submit-a-review', $newvalue );
	
		global $wpdb;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		foreach (static::$activationHooks as $hook) {
	     $hook($wpdb);
		}
		
		
		
		
	}


    public static function deactivate()
    {
		
		$page_id = get_option('submit-a-review');
        wp_delete_post($page_id);
		
		wp_delete_post( $the_page_id, true );
		 
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        foreach (static::$deactivationHooks as $hook) {
            $hook($wpdb);
        }
    }

}