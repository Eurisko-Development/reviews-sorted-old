<?php

use Reviews\Foundation\Installer;

Installer::boot(function ($wpdb) {
	// Reviews table
	$table_name = $wpdb->prefix . 'reviews';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
		`authorfname` VARCHAR(255) NOT NULL,
		`authorlname` VARCHAR(255) NOT NULL,
		`state` VARCHAR(255) NOT NULL,
		`phone` VARCHAR(20) NOT NULL,
		`email` VARCHAR(100) NOT NULL,
		`region` VARCHAR(10) NOT NULL,
		`branch` VARCHAR(100) NOT NULL,						
		`userip` VARCHAR(100) NOT NULL,
		`content` TEXT NOT NULL,
		`rating` decimal(2,1) NOT NULL,
		`recommend` VARCHAR(255) NOT NULL,
		`status` VARCHAR(10) NOT NULL,
		`questionnaire` TEXT NOT NULL,
		`created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY id (id)
	) $charset_collate;";

	dbDelta($sql);
	
	// Reviews Email table	
	
	$table_email = $wpdb->prefix . 'reviews_email';
	$charset_collates = $wpdb->get_charset_collate();
	$sqls = "CREATE TABLE $table_email (
	`id` INT(255) NOT NULL AUTO_INCREMENT, 
	`email` VARCHAR(255) NOT NULL ,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,     UNIQUE KEY id (id)
	) $charset_collates;";
	
	dbDelta($sqls);
	
	
 
	// Create key
	if ( ! file_exists(__DIR__ .'/../key.php')) {
		file_put_contents(__DIR__ .'/../key.php', "<?php\n\ndefine('WH_REVIEW_KEY', '". sha1(time() . microtime(true)) ."');");
	}
});