<!doctype html>
<html>
<head>
<meta charset="utf-8">
</head>

<body>
<h1 style="font-family: Arial, Helvetica, sans-serif; font-size: 24px; line-height: 28px; color: #222222;">Feedback</h1><p>The following feedback has been added to the website. Please review and manage accordingly.</p>

<?php
	$barColour = 'red';
	if ($review->rating == 3) {
		$barColour = 'orange';
	}
	if ($review->rating >= 4) {
		$barColour = 'green';
	}
	if ($review->questionnaire['recommend'] == 'No') {
		$barColour = 'red';
	}
?>
<?php
	$fields = [
		'authorfname' => ' First Name',			
		'authorlname' => ' Last Name',					
		'state'		=> ' State',
		'phone' => 'Phone',
		'email' => 'Email',
		'rating' => 'Rating',
		'recommend' => 'Would you recommend '. get_bloginfo('name').' to your family and friends?',
		'content' => 'Feedback',
		'status' => 'Current Status'
	];
?>

<table cellspacing="0" cellpadding="8" style="border-collapse: collapse;" border="0">
<tr>
	<td style="height: 10px; border-top: 10px solid <?= $barColour ?>">&nbsp;</td>
	<td style="height: 10px; border-top: 10px solid <?= $barColour ?>">&nbsp;</td>
</tr>

<?php foreach ($fields as $field => $label): ?>

<tr>
	<td valign="top" align="left" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; color: #222222; border-bottom: 1px solid #e5e5e5;"><strong><?= $label ?></strong></td>
	<td valign="top" align="left" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; color: #222222; border-bottom: 1px solid #e5e5e5;">
		<?php
			if (stristr($field, '.') !== false) {
				list($field, $key) = explode('.', $field);
				$value = $review->$field;
				$value = $value[$key];
			} else {
				$value = $review->$field;
			}
			if ($field == 'rating') {
				$value .= $value == 1 ? ' Star' : ' Stars';
			}
			if ($field == 'content') {
				$value = stripslashes(nl2br($value));
			}
			echo $value;
		?>
	</td>
</tr>
<?php endforeach; ?>
<tr>
	<td valign="top" align="left" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; color: #222222; border-bottom: 1px solid #e5e5e5;"><strong>Date submitted</strong></td>
	<td valign="top" align="left" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; color: #222222; border-bottom: 1px solid #e5e5e5;">
		<?php
			$date = new \DateTime($review->created_at);
  			$date->setTimezone(new \DateTimeZone('Australia/Perth'));
			echo $date->format('d/m/Y H:i');
		?>
	</td>
</tr>
</table>



</body>
</html>
