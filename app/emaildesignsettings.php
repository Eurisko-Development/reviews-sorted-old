<?php



use Reviews\Foundation\Config;



use Reviews\Foundation\Setting;


$Emaildesign = [	


'email-heading-content' => [

		'name' => 'E-Mail Template Heading Content ',
		
		
	],

	'email-footer-content' => [

		'name' => 'E-Mail Template Footer Content ',
		
		
	],
	

	
	'emaildesign' => [			
	'name' => 'E-Mail Design',			
	'field' => 'hidden'		],
	
	'email-container-bgcolor' => [

		'name' => 'E-Mail Template Container Backgournd Color',

	],
	
	'email-table-bgcolor' => [

		'name' => 'E-Mail Template Logo Section Backgournd Color',

	],
	
	'email-heading-color' => [

		'name' => 'E-Mail Template Heading Content Color',

	],
	
	'email-table-label-color' => [

		'name' => 'E-Mail Template Heading Label Color',

	],
	
	'email-table-content-color' => [

		'name' => 'E-Mail Template Table Content Color',

	],
	
	'email-footer-color' => [

		'name' => 'E-Mail Template Footer Content Color',

	],
	
	'email-table-row-color' => [

		'name' => 'E-Mail Template Row Backgournd Color',

	],
	
	
	
];


Setting::add('reviews-email-template', $Emaildesign);