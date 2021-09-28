<?php
/*
Plugin Name:  Reviews Sorted
Description: Manage your online reputation and collect verified customer reviews that you can publish to your website, your social media & pages & third-party review websites. Build your online reputation by promoting positive reviews and manage negative reviews before they become a reputation nightmare.
Version: 1.0.1
Author: <a href="http://www.reviewssorted.com">Reviews Sorted</a>
*/

use Reviews\Foundation\View;
use Reviews\Foundation\Route;
use Reviews\Foundation\Installer;
//use Review\app\Controllers\Admin\ActivationController; 
require __DIR__ .'/bootstrap.php';

session_start();
// Initialization
@View::init(__DIR__);
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
		
     if ( !in_array($_REQUEST['page'],$page)) {
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

if($selectOptions['activation'] == 'false' ) {		 

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

if($selectOptions['activation'] == 'true' && get_option( 'my_run_only_once_02' ) != 'completed'){
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