<?php

use Reviews\Foundation\View;
use Reviews\Foundation\Config;

?>

<?php $_View = new View();
	  $_View->startSection('breadcrumbs') ?>
	<h1>Reviews List</h1>
<?php 
	  $_View->endSection('breadcrumbs') ?>

<?php 
	  $_View->startSection('content') ?>
	  
<?php
	// $headers = array( 'Content-Type: text/html; charset=UTF-8' );
	// $date = new DateTime();
	// $date->setTimezone(new DateTimeZone('Australia/Perth'));
	
	// wp_mail( 'phongtran255@gmail.com', 'Email scheduler Cron Run', 'Date: ' . $date->format('d/m/Y H:i'), $headers, array( '' ) );
	
	// $templates = get_option('reviews_popup_email_template'); 
	// $schedules  = [
		// 'Thank You For Your Review' => ['+1 day', ['rating' , [5.0, 4.0], 'in']],
		// 'Referral Request' 			=> ['+30 day', ['recommend' , 'yes']],
		// '1 Star Review' 			=> ['+2 day', ['rating' , [1.0], 'in']],
	// ];
	
	
	
	// global $wpdb;
	// $table_reviews = "{$wpdb->prefix}reviews";
	// $table_name    = "{$wpdb->prefix}email_schedule";
	// $query_date    = $date->format('Y-m-d');
	// $results       = $wpdb->get_results( $wpdb->prepare(
		// "
			// SELECT s.*, r.email  
			// FROM {$table_name} s
			// INNER JOIN {$table_reviews} r ON r.id = s.review_id 
			// WHERE s.date_send = %s AND s.status = %d
		// ",
		// $query_date,
		// 0
	// ) );
	
	// var_dump($results);
	
	// foreach($results as $row){
		// $subject = $row->email_template;
		// $message = isset($templates[$subject]) ? $templates[$subject] : '';
		// if(empty($message)){
			// continue;
		// }
		// $message = apply_filters( 'the_content', $message); 
		// $message = str_replace(
			// array('*|FNAME|*', '*|STARRATING|*', '*|FEEDBACK|*', '*|EMAIL|*'),
			// array(
				// $review->authorfname,
				// $review->rating,
				// $review->content,
				// $review->email,
			// ),
			// $message
		// );
				
		// $to = $row->email; // 'phongtran255@gmail.com'; // 
				
		// $sent = wp_mail( $to, $subject, $message, $headers, array( '' ) );
		// $wpdb->query( $wpdb->prepare( 
			// "
				// UPDATE {$table_name}
				// SET status = %d
				// WHERE ID = %d
			// ",
			// 1,
			// intval($row->ID)
		// ) );
	// }
	
	
?>	  

<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
	<th style=" width: 30px;">ID</th>
	<th style=" width: 120px;">Date/Time</th>
	<th style=" width: 120px;">Customer</th>
	<th style=" width: 180px;">Email</th>
	<th style=" width: 100px;">Recommend</th>
	<th style=" width: 100px;">Rating</th>
	<th>Email schedule</th>
</tr>
</thead>
<tbody><?php if(empty($reviews)){?><tr><td colspan="9"> No Reviews</td></tr>
<?php } foreach ($reviews as $review): 

?> 
<tr>
	<td><?= (int)$review->id ?></td>
	<td>
		<?php 
			$date = new DateTime($review->created_at); 
			$date->setTimezone(new DateTimeZone('Australia/Perth'));
			
			echo $date->format('d/m/Y H:i');
		?>
	</td>
	<td><?= esc_html($review->authorfname."&nbsp;".$review->authorlname) ?></td>
	
	<td><?= esc_html($review->email) ?></td>
	<td><?= esc_html($review->recommend); ?></td>
	<td><?= esc_html($review->rating); ?></td>
	<td>
		<?php 
			$av_stars = floor($review->rating * 2) / 2;
			$av_stars = str_replace(".","-", $av_stars);
			$current_date = date('d/m/Y H:i');
			$now    = new DateTime();
			$emails = get_email_schedule_by_review_id($review->id);
			// var_dump($emails);
			if($emails){
				echo '<table>';
				foreach($emails as $m){
					$status = ($m->status == '0') ? 'Waiting' : ($m->status == '1' ? "Sent" : "Disable");
					echo '<tr>';
						// echo '<td><span style="display: none;">'. $m->ID .'</span></td>';
						echo '<td style=" width: 200px; ">'. $m->email_template .'</td>';
						echo '<td>'. $m->date_send .'</td>';
						echo '<td>'. $status .'</td>';
						echo '<td>
							<a href="#" data-id="'. $m->ID .'" data-action="disable">Disable</a>
							 | 
							<a href="#" data-id="'. $m->ID .'" data-action="active">Active</a>
							 |							 
							<a href="#" data-id="'. $m->ID .'" data-action="sendnow">Send now</a>
						</td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			else{
				echo '<a href="#" data-id="'. $review->id .'" data-action="addschedule">add schedule</a>';
			}
		?>
	</td>
	
</tr>
<?php endforeach; ?>
</tbody>
</table>

<span class="pagination-links">
	<?php
		$page = isset($_GET['paged']) ? $_GET['paged'] : 1;
	?>
	<?= paginate_links(array(
		'total' => $pages,
		'current' => $page,
		'format' => '&paged=%#%',
		'base' => admin_url('admin.php?page=reviews%_%')
	)) ?>
</span>
<style>
.loader-wrap {
	display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgb(0 0 0 / 75%);
    z-index: 10;
}
.loader {
  border: 16px solid #f3f3f3; /* Light grey */
  border-top: 16px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 120px;
  height: 120px;
  animation: spin 2s linear infinite;
	position: absolute;
	top: 50%;
	left: 50%;
	margin-top: -60px;
	margin-left: -60px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
<div class="loader-wrap">
	<div class="loader"></div>
</div>
<script>
	jQuery(document).ready(function($){
		var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
		$('[data-action="addschedule"]').on('click', function(){
			$('.loader-wrap').show();
			var id = $(this).data('id');
			var data = {
				'action': 'addschedule',
				'id': id
			};
			jQuery.post(ajax_url, data, function(response) {
				console.log(response);
				location.href = location.href;
			});
			
			return false;
		});
		
		$('[data-action="sendnow"],[data-action="disable"],[data-action="active"]').on('click', function(){
			$('.loader-wrap').show();
			var data = {
				'task': $(this).data('action'),
				'id': $(this).data('id'),
				'action': 'schedule_action'
			};
			jQuery.post(ajax_url, data, function(response) {
				console.log(response);
				location.href = location.href;
			});
			
			return false;
		});
	})
</script>

<?php 
	  $_View->endSection('content') ?>
<?php echo $_View->make('layouts.main') ?>