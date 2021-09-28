<?php

use Reviews\Foundation\View;
use Reviews\Foundation\Config;


$id = $_POST['id'];
global $wpdb;
$table_name = $wpdb->prefix . 'reviews';
	$result= $wpdb->query
	( 
		$wpdb->prepare
		( 
		"DELETE FROM $table_name  WHERE id = %d",$id
		)
	);


if( FALSE === $result ) {
    echo( "Failed!" );
} else {
    echo( "Great success!" );
}

return $result;
?> 