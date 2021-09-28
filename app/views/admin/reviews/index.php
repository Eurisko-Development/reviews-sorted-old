<?php

use Reviews\Foundation\View;
use Reviews\Foundation\Config;

?>

<?php View::startSection('breadcrumbs') ?>
	<h1>Reviews List</h1>
<?php View::endSection('breadcrumbs') ?>

<?php View::startSection('content') ?>

<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
	<th>ID</th>
	<th>Date/Time</th>
	<th>User IP</th>
	<th>Customer</th>
	<th>Email</th>
	<th>Phone</th>
	<!--th>Branch</th-->
	<th>Star Rating</th>
	<th>Status</th>
	<th>Actions</th>
</tr>
</thead>
<tbody><?php if(empty($reviews)){?><tr><td colspan="9"> No Reviews</td></tr>
<?php } foreach ($reviews as $review): ?> 
<tr>
	<td><?= (int)$review->id ?></td>
	<td>
		<?php 
			$date = new DateTime($review->created_at); 
			$date->setTimezone(new DateTimeZone('Australia/Perth'));
			
			echo $date->format('d/m/Y H:i');
		?>
	</td>
	<td><?= esc_html($review->userip) ?></td>
	<td><?= esc_html($review->authorfname."&nbsp;".$review->authorlname) ?></td>
	
	<td><?= esc_html($review->email) ?></td>
	<td><?= esc_html($review->phone) ?></td>
	<!--td><!-?= esc_html($review->branch) ?-></td-->
	<td><?= esc_html($review->rating) ?></td>
	<td><?= esc_html($review->status) ?></td>
	<td>
		<span class="edit">
			<a  class="reviwe_edit"  id="<?php echo $review->id; ?>" href="<?= admin_url('admin.php?page=reviews/edit&id='. $review->id) ?>" title="Edit this item">Edit</a>
		</span>
		<span>|</span>
		<span class="trash" id="record-,<?php echo $review->id; ?>">
			<a class="submitdelete" id="<?php echo $review->id; ?>" href="javascript:;" >Trash</a>			
		</span>
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


<?php View::endSection('content') ?>
<?php echo View::make('layouts.main') ?>