<?php
namespace Reviews\Application\Reviews\Controllers\Admin;
use Reviews\Foundation\View;
use Reviews\Foundation\Validator;
use Reviews\Foundation\FlashMessage;
use Reviews\Application\Reviews\Models\Review;
use Reviews\Application\Reviews\Requests\Review as ReviewRequest;

class EmailController
{
	public static function index() 
	{
		$_View = new View();
		$_View->render('admin.email.index', array(), true);
	}	
	
	public static function template() 	{		
		$_View = new View();
		$_View->render('admin.email.template', array(), true);	
	}
	public static function schedule() 	{	
		
		
		$limit = 20;
		$query = Review::query();
		// if (isset($_GET['filter-region']) && $_GET['filter-region']) {
			// $query->where('region', $_GET['filter-region']);
		// }
		// if (isset($_GET['filter-branch']) && $_GET['filter-branch']) {
			// $query->where('branch', $_GET['filter-branch']);
		// }
		
		// eurisko_email_schedule_run_cron();
		
		$total = $query->count();
		$reviews = $query->latest()->paginate($limit);
		$pages = ceil($total / $limit);
			
		$_View = new View();
		$_View->render('admin.email.schedule', compact('reviews', 'pages'), true);	
	}
}

// function eurisko_email_schedule_cron_deactivate() {
    // wp_clear_scheduled_hook( 'eurisko_email_schedule_cron' );
// }
 
// add_action('init', function() {
    // add_action( 'eurisko_email_schedule_cron', 'eurisko_email_schedule_run_cron' );
    // register_deactivation_hook( __FILE__, 'eurisko_email_schedule_cron_deactivate' );
 
    // if (! wp_next_scheduled ( 'eurisko_email_schedule_cron' )) {
        // wp_schedule_event( time(), 'daily', 'eurisko_email_schedule_cron' );
    // }
// });
 

