<?php



use Reviews\Foundation\Config;



$pluginName = 'Reviews';



require __DIR__ .'/autoloader.php';

new ReviewsAutoloader($pluginName);



// Core parts

require __DIR__ .'/app/installer.php';

require __DIR__ .'/app/settings.php';

require __DIR__ .'/app/formsettings.php';

require __DIR__ .'/app/emailsettings.php';

require __DIR__ .'/app/emaildesignsettings.php';





// Parts

require __DIR__ .'/app/assets.php'; 

require __DIR__ .'/app/shortcodes.php';

require __DIR__ .'/app/routes.php';

require __DIR__ .'/app/admin.php';

require __DIR__ .'/app/schedules.php';



// Key

if (file_exists(__DIR__ .'/key.php')) {

	require __DIR__ .'/key.php';

}



add_action('admin_menu', ['Reviews\Foundation\Admin', 'boot']);

add_action('admin_init', ['Reviews\Foundation\Setting', 'boot']);

add_action('admin_init', ['Reviews\Foundation\FormSetting', 'boot']);

add_action('admin_init', ['Reviews\Foundation\EmailSetting', 'boot']);

add_action('admin_init', ['Reviews\Foundation\Functions', 'boot']);

