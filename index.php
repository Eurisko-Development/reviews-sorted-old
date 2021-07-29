<?php

/*

Plugin Name:  Reviews Sorted

Description: Manage your online reputation and collect verified customer reviews that you can publish to your website, your social media & pages & third-party review websites. Build your online reputation by promoting positive reviews and manage negative reviews before they become a reputation nightmare.

Version: 1.0.2

Author: <a href="http://www.reviewssorted.com">Reviews Sorted</a>

*/



define('Thank_You_For_Your_Review', '+1 day');

define('Referral_Request', '+30 day');

define('One_Star_Review', '+2 day');



use Reviews\Foundation\View;

use Reviews\Foundation\Route;

use Reviews\Foundation\Installer;

//use Review\app\Controllers\Admin\ActivationController; 

require __DIR__ .'/bootstrap.php';



session_start();

// Initialization

$_View = new View();

$_View->init(__DIR__);

// View::

Installer::init(__FILE__);

Route::init(__DIR__, 'Reviews');



/** Fitter to add Setting link in plugin 22-feb-2019 **/



add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'review_add_plugin_page_settings_link');



	function review_add_plugin_page_settings_link( $links ) 

	{

	$links[] = '<a href="' .

	admin_url( 'admin.php?page=reviews' ) .

	'">' . __('Settings') . '</a>';

	return $links;



	}		



	





    function ln_reg_css_and_js($hook)

    {



 	$page=array('reviews/email-setting','reviews/forms','reviews','reviews/settings','reviews/email-template-design','reviews/mailchimp','reviews/email-template');

		

     if ( !isset($_REQUEST['page']) || !in_array($_REQUEST['page'],$page)) {

		 return;

    } else {

	

        wp_enqueue_style('boot_css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css');

        wp_enqueue_script('boot_js','https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js');

        wp_enqueue_script('ln_script', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js', ['jquery'], false, true);

		

        }

    }

	

add_action('admin_enqueue_scripts', 'ln_reg_css_and_js');	

	

	/** Show Notice on plugin for  Activation key **/





$selectOptions=get_option('reviews-activationkey-settings');



if(!isset($selectOptions['activation']) || $selectOptions['activation'] == 'false' ) {		 



add_action( 'admin_notices', 'sample_admin_notice__error' );		

}		



function sample_admin_notice__error() {?>

<div class="notice notice-error is-dismissible ">



<p><?php _e( 'Please Enter Vaild Activation Key to Access the Plugin!', 'sample-text-domain' ); ?></p>		

</div>		

<?php

		

}











function my_run_only_onces() {



$selectOptions=get_option('reviews-activationkey-settings');

if(get_option( 'my_run_only_once_02' ) != ' '){



if(isset($selectOptions['activation']) && $selectOptions['activation'] == 'true' && get_option( 'my_run_only_once_02' ) != 'completed'){

add_action( 'admin_notices', 'sample_admin_notice__success' );	



}

function sample_admin_notice__success() {

?>

<div class="notice notice-success is-dismissible activation">

<p><?php _e( 'Plugin Activated Successfully !', 'sample-text-domain' ); ?></p>

</div>

<?php



update_option( 'my_run_only_once_02', 'completed' );

}	



}

}

	

add_action( 'admin_init', 'my_run_only_onces' );









add_action( 'wp_ajax_my_action', 'my_action' );



function my_action() {

	global $wpdb; // this is how you get access to the database



	

	if(!empty($_POST['activation_key']) && $_POST['activation_key'] == '85545eee520bb172025db97b266cbc7afe7f6c1c'){



		  $options = array( 

					'reviews-activation_key'=>$_POST['activation_key'],

					'activation'=>'true',

					'activation_key'=>'85545eee520bb172025db97b266cbc7afe7f6c1c'

					);	

				

				

					update_option( 'reviews-activationkey-settings', $options );

					

        

		

 }else{



			$options = array( 

					'reviews-activation_key'=>$_POST['activation_key'],

					'activation'=>'false',

					'activation_key'=>'85545eee520bb172025db97b266cbc7afe7f6c1c'

					);	

				

				

					update_option( 'reviews-activationkey-settings', $options );

					

		

 }

 

	exit; // this is required to terminate immediately and return a proper response

}







/* To Save Html Editor Data 12-Apr-2019*/



add_action( 'wp_ajax_Editor', 'Editor' );

add_action('wp_ajax_nopriv_Editor', 'Editor');

function Editor()

	{

	$wp_editor_val=" ";

	$currenttemplate=$_POST['currenttemplate'];

		





	   $selectOptions=get_option('reviews_popup_email_template');

	   

		foreach($selectOptions as $option_name => $option_val){

	  if($option_name == $currenttemplate ){

			   

		  $wp_editor_val.=$option_val;  

		   }

		}

	   

	

	  echo $wp_editor_val;

	  

	  exit;

	  

						

	}













add_action( 'wp_ajax_SaveEditerData', 'SaveEditerData' );

add_action('wp_ajax_nopriv_SaveEditerData', 'SaveEditerData');

function SaveEditerData(){

$selectOptions=get_option('reviews_popup_email_template');	

$options=array($_POST['templatename']=>stripslashes($_POST['data']));

if(!empty(selectOptions)){

foreach($selectOptions as $option_name => $option_val){

	

$option[$option_name] = $option_val;

					//$_POST['templatename']=>stripslashes($_POST['data']),

					

					



}

			

$email_template=array_merge($option,$options);	



}





update_option( 'reviews_popup_email_template',$email_template);



}





add_action( 'wp_ajax_SaveNewTemplate', 'SaveNewTemplate' );

add_action('wp_ajax_nopriv_SaveNewTemplate', 'SaveNewTemplate');

function SaveNewTemplate(){

$selectOptions=get_option('reviews_popup_email_template');	

$options=array($_POST['templatename']=>stripslashes($_POST['data']));

if(!empty(selectOptions)){

foreach($selectOptions as $option_name => $option_val){

	

$option[$option_name] = $option_val;

					//$_POST['templatename']=>stripslashes($_POST['data']),

					

					



}

			

$email_template=array_merge($option,$options);	



}





update_option( 'reviews_popup_email_template',$email_template);



}















add_action( 'wp_ajax_UpdateTemplate', 'UpdateTemplate' );

add_action('wp_ajax_nopriv_UpdateTemplate', 'UpdateTemplate');



function UpdateTemplate(){

	

$selectOptions=get_option('reviews_popup_email_template');

//$options=array($_POST['templatename']=>stripslashes($_POST['data']));	

if($_POST['templatename'] === $_POST['newtemplatename']){

	

	foreach($selectOptions as $option_name => $option_val){

		

		if($option_name == $_POST['templatename'] ){

	

        $option[$option_name] = stripslashes($_POST['data']);

			

		}else{

			

			$option[$option_name] = $option_val;

		}

					



}

	

	

}else{

	foreach($selectOptions as $option_name => $option_val)

	{

		if($option_name == $_POST['templatename'] ){

	

        $option[$_POST['newtemplatename']] = stripslashes($_POST['data']);

			

		}else{

			

			$option[$option_name] = $option_val;

		}

	}



}





update_option( 'reviews_popup_email_template',$option);



}









add_action( 'wp_ajax_DeleteTemplate', 'DeleteTemplate' );

add_action('wp_ajax_nopriv_DeleteTemplate', 'DeleteTemplate');



function DeleteTemplate(){

	

$selectOptions=get_option('reviews_popup_email_template');

$options=array($_POST['templatename']=>stripslashes($_POST['data']));	

if($_POST['templatename'] === $_POST['newtemplatename']){

	

	foreach($selectOptions as $option_name => $option_val){

		

		if($option_name == $_POST['templatename'] ){

	

          unset($option[$option_name]);

			

		}else{

			

			$option[$option_name] = $option_val;

		}

					



}



}



update_option( 'reviews_popup_email_template',$option);



}





add_action('reviews_after_submit_review', 'reviews_add_schedule_after_submit_review', 10, 1);

function reviews_add_schedule_after_submit_review($review){

	global $wpdb;

	

	//NULL, '9', 'Thank You For Your Review', '2021-03-21', '0'

	$table_name = "{$wpdb->prefix}email_schedule";

	$av_stars = floor($review->rating * 2) / 2;

	

	//- Thank You For Your Review

	if(in_array($review->rating, array('5.0', '4.0', 5, 4))){

		$date = new DateTime($review->created_at); 

		$date->setTimezone(new DateTimeZone('Australia/Perth'));

		$send_date = $date; 

		$send_date->modify(Thank_You_For_Your_Review);

		$wpdb->query(

		   $wpdb->prepare(

			  "INSERT INTO {$table_name}

			  ( `ID`, `review_id`, `email_template`, `date_send`, `status` )

			  VALUES ( NULL, %d, 'Thank You For Your Review', %s, 0 )

			  ",

			  intval($review->id),

			  $send_date->format('Y-m-d')

		   )

		);

	}

	

	//- Referral Request

	if($review->recommend == 'Yes'){

		$date = new DateTime($review->created_at); 

		$date->setTimezone(new DateTimeZone('Australia/Perth'));

		$send_date = $date; 

		$send_date->modify(Referral_Request);

		$wpdb->query(

		   $wpdb->prepare(

			  "INSERT INTO {$table_name}

			  ( `ID`, `review_id`, `email_template`, `date_send`, `status` )

			  VALUES ( NULL, %d, 'Referral Request', %s, 0 )

			  ",

			  intval($review->id),

			  $send_date->format('Y-m-d')

		   )

		);

	}



	//- 1 Star Review

	if(in_array($review->rating, array('1.0', 1))){

		$date = new DateTime($review->created_at); 

		$date->setTimezone(new DateTimeZone('Australia/Perth'));

		$send_date = $date; 

		$send_date->modify(One_Star_Review);

		$wpdb->query(

		   $wpdb->prepare(

			  "INSERT INTO {$table_name}

			  ( `ID`, `review_id`, `email_template`, `date_send`, `status` )

			  VALUES ( NULL, %d, '1 Star Review', %s, 0 )

			  ",

			  intval($review->id),

			  $send_date->format('Y-m-d')

		   )

		);

	}

}



function get_email_schedule_by_review_id($submit_id){

	global $wpdb;

	$table_name = "{$wpdb->prefix}email_schedule";

	$results = $wpdb->get_results( 

		"

			SELECT * 

			FROM {$table_name}

			WHERE review_id = {$submit_id}

		"

	);

	

	return $results;

}



function get_email_schedule_by_id($submit_id){

	global $wpdb;

	$table_name = "{$wpdb->prefix}email_schedule";

	$results = $wpdb->get_row( 

		"

			SELECT * 

			FROM {$table_name}

			WHERE ID = {$submit_id}

		"

	);

	

	return $results;

}



function get_review_by_id($review_id){

	global $wpdb;

	$table_name = "{$wpdb->prefix}reviews";

	$results = $wpdb->get_row( 

		"

			SELECT * 

			FROM {$table_name}

			WHERE id = {$review_id}

		"

	);

	

	return $results;

}



add_action( 'wp_ajax_addschedule', 'review_ajax_add_schedule_callback' );

function review_ajax_add_schedule_callback(){

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

	if(!$id){

		echo '10'; exit();;

	}

	

	$review = get_review_by_id($id);

	if($review){

		reviews_add_schedule_after_submit_review($review);

	}	

	echo '1'; exit();

}



add_action( 'wp_ajax_schedule_action', 'review_ajjax_schedule_action_callback' );

function review_ajjax_schedule_action_callback(){

	$id   = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

	$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';

	if(!$id){

		echo '10'; exit();;

	}

	

	$schedule = get_email_schedule_by_id($id);

	$review   = get_review_by_id($schedule->review_id);

	

	if($schedule && $review){

		global $wpdb;

		$table_name    = "{$wpdb->prefix}email_schedule";

		if($task == 'disable'){

			$wpdb->query( $wpdb->prepare( 

				"

					UPDATE {$table_name}

					SET status = %d

					WHERE ID = %d

				",

				2,

				$id

			) );

		}

		else if($task == 'active'){

			$wpdb->query( $wpdb->prepare( 

				"

					UPDATE {$table_name}

					SET status = %d

					WHERE ID = %d

				",

				0,

				$id

			) );

		}

		else if($task == 'sendnow'){

			$templates = get_option('reviews_popup_email_template'); 

			

			$subject = $schedule->email_template;

			$message = isset($templates[$subject]) ? $templates[$subject] : '';

			if(empty($message)){

				echo '11'; exit();

			}

			$message = apply_filters( 'the_content', $message); 

			$message = str_replace(

				array('*|FNAME|*', '*|LNAME|*', '*|STARRATING|*', '*|FEEDBACK|*', '*|EMAIL|*'),

				array(

					$review->authorfname,

					$review->authorlname,

					$review->rating,

					$review->content,

					$review->email,

				),

				$message

			);

					

			$to = $review->email; // 'phongtran255@gmail.com'; // 

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );	

			$sent = wp_mail( $to, $subject, $message, $headers, array( '' ) );

			$wpdb->query( $wpdb->prepare( 

				"

					UPDATE {$table_name}

					SET status = %d

					WHERE ID = %d

				",

				1,

				$id

			) );

			

		}

	}	

	echo '1'; exit();

}



