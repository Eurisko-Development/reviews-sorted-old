<?php
use Reviews\Foundation\Admin;
Admin::pages('reviews', 'Reviews', 'Reviews', 
['Manage Activation ' => [ 		
'slug' => '',				
'uses' => 'Admin\ActivationController@index',
'capability' => 'edit_posts'	
		],
], 'edit_posts');
$selectOptions=get_option('reviews-activationkey-settings');


if( isset($selectOptions['activation']) &&  $selectOptions['activation'] == 'true'){	
Admin::pages('reviews', 'Reviews', 'Reviews', [
	'Form Settings' => [ 
		'slug' => 'forms',	
		'uses' => 'Admin\FormController@index',	
		'capability' => 'edit_posts'	
	],

	'General Settings' => [
		'slug' => 'settings',
		'uses' => 'Admin\SettingsController@index',
		'capability' => 'edit_posts'
	]
	,		
	'Reviews List' => [	
		'slug'  => '',
		'uses'  => 'Admin\ReviewsController@index',
		'capability' => 'edit_posts',
		'pages' => [
			[
				'slug' => 'edit',
				'uses' => 'Admin\ReviewsController@edit'
			],
			[
				'slug' => 'update',
				'uses' => 'Admin\ReviewsController@update'	
			],
			[
				'slug' => 'delete',
				'uses' => 'Admin\ReviewsController@delete'	
			]	
		]	
	],
	
	'Email schedule' => [
		'slug' => 'email-schedule',
		'uses' => 'Admin\EmailController@schedule',

		'capability' => 'edit_posts'
	],		  		

	
	'Email' => [
		'slug' => 'email-setting',
		'uses' => 'Admin\EmailController@index',

		'capability' => 'edit_posts'
	],		  		

	'Email Template' => [
		'slug' => 'email-template',
		'uses' => 'Admin\EmailController@template',

		'capability' => 'edit_posts'
	],		  		


	'Email Template Design' => [
		'slug' => 'email-template-design',
		'uses' => 'Admin\EmaildesignController@index',

		'capability' => 'edit_posts'
	],

	'Mailchimp Settings' => [
		'slug' => 'mailchimp',
		'uses' => 'Admin\MailchimpController@index',
		'capability' => 'edit_posts'
	]		
],
 'edit_posts');}
add_action( 'admin_init', 'automasters_remove_menu_pages' );
function automasters_remove_menu_pages()
{
    $current_user = wp_get_current_user();
    if ( !($current_user instanceof WP_User) )
       return;
    $roles = $current_user->roles;
    if (in_array('contributor', $roles)) {
        remove_menu_page('upload.php'); // Media
        remove_menu_page('link-manager.php'); // Links
        remove_menu_page('edit-comments.php'); // Comments
        remove_menu_page('edit.php?post_type=page'); // Pages
        remove_menu_page('plugins.php'); // Plugins
        remove_menu_page('themes.php'); // Appearance
        remove_menu_page('users.php'); // Users
        remove_menu_page('tools.php'); // Tools
        remove_menu_page('options-general.php'); // Settings
        remove_menu_page('edit.php?post_type=news'); // News
        remove_menu_page('admin.php?page=vc-welcome');
        remove_menu_page('admin.php?page=acf-options-logo-carousel');
        remove_menu_page('admin.php?page=wpseo_tutorial_videos');
        remove_menu_page('edit.php?post_type=faq');
        remove_menu_page('admin.php?page=easy-modal');
        remove_menu_page('edit.php?post_type=edge');
        remove_menu_page('edit.php');
        remove_menu_page('edit.php?post_type=ttshowcase');
    }
}
