<?php
use Reviews\Foundation\Config;
use Reviews\Foundation\Setting;
use Reviews\Foundation\Mailchimp;
$settings = [
	'redirect_page' => [
		'name' => 'Redirect Page'
	],
	'homepage_tooltip' => [
		'name' => 'Tooltip Home Page',
		'field' => 'textarea'
	],
	
	'notification_emails_wa' => [
		'name' => 'Notification Emails',
		'field' => 'textarea'
	],
	 
	'overall_rating_label' => [
		'name' => 'Overall Rating Month Label',
		'field' => 'select'
	],
	
	'email_sender_name' => [
		'name' => 'Sender - "From" name'
	],
	'email_sender_address' => [
		'name' => 'Sender - "From" address'
	],
	
];
 $selectOption=get_option('reviews-mailchimp-settings'); 
 if(!$selectOption){
 	$selectOption = array();
 }
 $listfitter=sizeof($selectOption);
 $customurl = [];
	if(!empty($selectOption)) // 22-feb-2019 
	{
		foreach($selectOption as $key => $name)
		{
			
			for($i=0; $i<=$listfitter; $i++)
			{
				if("mailchimpcustomUrl".$i == $key ){
					$customurl[]=$key;
					
				}
			}
		}
	 }
 if(!empty($selectOption['mailchimpApikey'])){
	 $mailsettings =[
	'mailchimpApikey' => [
		'name' => 'Enter your MailChimp API Key (Save). Then select your destination list
(save)'
	],
	'mailchimpList' => [
		'name' => 'Mailchimp List',
		'field' => 'select'
	],
	
	'fieldtype' => [
		'name' => 'Field Label and Type',
		'field' => 'hidden'
		
	],
	
	// 'mailchimpemail' => [
		// 'name' => 'Email Address',
		// 'field' => 'labels',
		
		
	// ],
	
	'mailchimpfname' => [
		'name' => 'First Name',
		'field' => 'labels',				
				
		
	],
	
	'mailchimplname' => [
		'name' => 'Last Name',
		'field' => 'labels',				
		
		
	],
	'mailchimpstarrating' => [
		'name' => 'Starrating',
		 'field' => 'labels',
		
	],
	'mailchimprecommend' => [
		'name' => 'Recommend',
		'field' => 'labels',
		
	],
	
	'mailchimpfeedback' => [
		'name' => 'Feedback',
		'field' => 'labels',				
		
		
	],
	
		
	'mailchimpcustomfields' =>
	['name' => 'Add New Fields',
	'field' => 'websiteurl'	
	],
	
	'mailchimpcustomUrl' => 
	[	
	'name' => 'Url',	
	'field' => 'customfields',
	],
 ];
}else{	
$mailsettings = [	
'mailchimpApikey' =>
 [	'name' => 
 'Enter your MailChimp API Key (Save). Then select your destination list
(save)']	
];
	foreach($customurl as $keys => $url){
		if(strlen($url) != '1'){
		 $url;
		$len=strlen($url);
		$urlname=substr($url,15);
		
		$mailsettings[$url] = [
		'name' => $urlname,
		'field' => 'customfields'
			];	
		}	
		
	} 
}
/*29-mar-2019*/
$activationkeysettings = 
[	
'reviews-activation_key' =>[
	'name' => 'Enter Activation Key'
	], 
	'activation' => [
	'name' => 'activation',
	'field' => 'text',
	'class' => 'hidden'
	],
	'activation_key' => 
	['name' =>'activation_key',
	'field' => 'text',
	'class' => 'hidden',
	
	] 
];   
  
Setting::add('reviews-activationkey', $activationkeysettings); 
Setting::add('reviews-mailchimp', $mailsettings);
Setting::add('reviews-reviews', $settings);
