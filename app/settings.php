<?php

use Reviews\Foundation\Config;

use Reviews\Foundation\Setting;

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

	'local_business_heading' => [ 

		'name' => 'LOCAL BUSINESS DETAILS',	

		'field' => 'hidden'	

	],

	'business_address' => [

		'name' => 'Business Address',

		'field' => 'text'

	],

	'business_phone' => [

		'name' => 'Business Phone No.',

		'field' => 'text'

	]


];

 


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

Setting::add('reviews-reviews', $settings);

