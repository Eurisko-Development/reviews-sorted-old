<?phpuse Reviews\Foundation\Config;use Reviews\Foundation\EmailSetting;$Emailsettings = [	'usersemail' => [	'name' => 'List of Users Email',	'field' => 'select'	],	'email-msg' => [		'name' => 'Email Message',		'field' => 'textarea'	]	];Emailsetting::add('reviews-email', $Emailsettings);