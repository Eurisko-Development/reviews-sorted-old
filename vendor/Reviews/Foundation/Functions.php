<?php

namespace Reviews\Foundation;

class Functions

{

		public function get_client_ip() 

		{

			$ipaddress = '';

			if (getenv('HTTP_CLIENT_IP'))

				$ipaddress = getenv('HTTP_CLIENT_IP');

			else if(getenv('HTTP_X_FORWARDED_FOR'))

				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');

			else if(getenv('HTTP_X_FORWARDED'))

				$ipaddress = getenv('HTTP_X_FORWARDED');

			else if(getenv('HTTP_FORWARDED_FOR'))

				$ipaddress = getenv('HTTP_FORWARDED_FOR');

			else if(getenv('HTTP_FORWARDED'))

			   $ipaddress = getenv('HTTP_FORWARDED');

			else if(getenv('REMOTE_ADDR'))

				$ipaddress = getenv('REMOTE_ADDR');

			else

				$ipaddress = 'UNKNOWN';

			echo  $ipaddress;

		}

	

	

		

		public function CheckEmailAlreadySend()

	{

	global $wpdb;

	$style = '';

	$rows = $wpdb->get_results("SELECT email FROM " . $wpdb->prefix . "reviews_email", ARRAY_A);

	$sendemails = '';

	foreach($rows as $key => $v)

		{

		foreach($v as $k)

			{

			$sendemails[] = $k;

			}

		}

	$blogusers = get_users('role__not_in=administrator');

	foreach($blogusers as $user)

		{

		if (in_array($user->user_email, $sendemails))

			{

			echo '<option  style="background:#337ab7; color:#fff;" name="useremail" vlaue="' . esc_html($user->user_email) . '" rows="5" cols="60">', esc_html($user->user_email) . '</option>';

			}

		  else

			{

			echo '<option  name="useremail" vlaue="' . esc_html($user->user_email) . '" rows="5" cols="60">', esc_html($user->user_email) . '</option>';

			}

		}

	}

   public function insertFromData($data)

	{

		

	global $wpdb;

	

	if (!empty($data['useremail']))

		{

		$msg= stripslashes($data['emailmsg']);

		foreach($data['useremail'] as $user){

			

		$rows = $wpdb->get_row('SELECT display_name FROM '. $wpdb->prefix .'users WHERE user_email ="'.$user.'" ', ARRAY_A);

	

		$to = $user;

		$subject = ' Please  review on recent transaction';

		$message = 'Hi   '. ucwords($rows['display_name']).',  '. $msg;

		$headers = "Content-Type: text/html; charset=UTF-8";

		$mail = wp_mail($to, $subject, $message,$headers);	

		}

		if (!$mail)

			{

			echo "<div id='overlay'></div>

			<div id='error' class='modal ' role='dialog'>

			<div class='modal-content'>

			<div class='msg' style='color:red;text-align:center;font-size: 16px;font-weight:800;padding: 60px;'>Error In Email Sending ! <br />

			<button type='button' class='btn btn-info btn-lg' data-dismiss='modal'>Ok</button>			</div>

			</div>

			</div>";

			print ("<script> jQuery('#error').show(); jQuery('.btn-info').click(function(){jQuery('#error,#overlay').hide();})</script>");

			}

		  else

			{

			echo "<div id='overlay'></div>

			<div id='success' class='modal' role='dialog'>

			<div class='modal-content'>

			<div class='msg' style='color:green;text-align:center;font-size: 16px;font-weight: 800;padding: 60px;'>Email Sent Successfully !  <br />

			<button type='button' class='btn btn-info btn-lg' data-dismiss='modal'>Ok</button>			</div>

			</div>

			</div>";

			print ("<script> jQuery('#success').show(); jQuery('.btn-info').click(function(){jQuery('#success,#overlay').hide(); })</script>");

			}

		global $wpdb;

		foreach($data['useremail'] as $useremail)

			{

			$sql = $wpdb->insert($wpdb->prefix . 'reviews_email', array(

				'email' => $useremail

			) , array(

				'%s'

			));

			}

		}

	elseif (!empty($data['email']) && $data['email'] != ' ')

		{

		$msg= stripslashes($data['emailmsg']);

		$to = $data['email'];

		$subject = ' Please  review on recent transaction';

		$message = 'Hi   ' .ucwords($data['fname']) .'  '. ucwords($data['lname']).',   '. $msg ;

		$mail = wp_mail($to, $subject, $message,$headers);

		if (!$mail)

			{

			echo "<div id='overlay'></div>

			<div id='error' class='modal' role='dialog'>

			<div class='modal-content'>

			<div class='msg' style='color:red;text-align:center;font-size: 16px;font-weight:800;padding: 60px;'>Error In Email Sending ! <br />

			<button type='button' class='btn btn-info btn-lg ' data-dismiss='modal'>Ok</button>			</div>

			</div>

			</div>";

			print ("<script> jQuery('#error').show(); jQuery('.btn-info').click(function(){jQuery('#error,#overlay').hide();})</script>");

			}

		  else

			{

			echo "<div id='overlay'></div>

			<div id='success' class='modal' role='dialog'>

			<div class='modal-content'>

			<div class='msg' style='color:green;text-align:center;font-size: 16px;font-weight: 800;padding: 60px;'>Email Sent Successfully ! <br />	

			<button type='button' class='btn btn-info btn-lg' data-dismiss='modal'>Ok</button>			</div>

			</div>

			</div>";

			print ("<script> jQuery('#success').show(); jQuery('.btn-info').click(function(){jQuery('#success,#overlay').hide();})</script>");

			}

		global $wpdb;

		$sql = $wpdb->insert($wpdb->prefix . 'reviews_email', array(

			'email' => $data['email']

		) , array(

			'%s'

		));

		$wpdb->query($sql);

		}

	}

public function TextareaEditor()

	{

		

		$page = home_url() . '/submit-a-review';

		

		$options = array( 

		'Defualt Template' =>'thanks for choosing Reviews! Would you take a moment to review your experience?'. '<a href=' . $page . '> review-us</a>',

		);	

		add_option( 'reviews_popup_email_template',$options);	

	   $selectOptions=get_option('reviews_popup_email_template');

	   

		foreach($selectOptions as $option_name => $option_val){

	  

	   if($option_name == 'Defualt Template'){

		  

		   $select=$option_val ;

		   

		   }

		}

	    wp_editor($select, 'popup_email_textarea', $settings = array(

		'textarea_name' => 'emailmsg',

		'editor_height' => 125,

		'textarea_rows' => 10,

		'tinymce' => false, 

	));

	echo '<br />

	 

	<select name="email_template" id="email_template" >

	<option name="">Choose Template</option>';

	

   foreach($selectOptions as $option_name => $option_val){

	   

	   $select='';

	   // if($option_name === 'Defualt Template'){

		  

		   // $select="selected" ;

		   

		   // }

	   

  echo '<option  name="' . esc_html($option_name) . '"  vlaue="' . esc_html($option_name) . '" rows="5" cols="60"' .$select .'>', esc_html($option_name) . '</option>    ';

   }

   echo ' 

    </select>

   <div  id="templatename-field"  class="single-field-wrap">

	<label for="lname">Template Name</label>

	<input type="text" name="tname" placeholder="eg. Default Template" id="tname"><br/><br/>

	<input type="button" value="Save"  id="savetemplate" name="" class="button button-primary" >

	</div>

   

    

	<br />

	<br />';

	 

	

	}

public function SaveNewTemplate()

	{

	$page = home_url() . '/submit-a-review';

	echo '<div  id="savenewtemplatename-field"  class="single-field-wrap" >

	<label for="lname">Template Name</label>

	<input type="text" name="tname" placeholder="eg. Default Template" id="tname" required>

	<p class="savenewtemplatenamemsg"></p>

	</div>';

   wp_editor('thanks for choosing Reviews! Would you take a moment to review your experience?'. '<a href=' . $page . '> review-us</a>', 'popup_email_textarea', $settings = array(

		'textarea_name' => 'emailmsg',

		'editor_height' => 125,

		'textarea_rows' => 10,

		'tinymce' => false, 

	));

	echo '<br />

	<select class="email_template_section"  name="email_template" id="email_template" ><option name="">Choose Template</option>';

	$selectOptions=get_option('reviews_popup_email_template'); 

	

   foreach($selectOptions as $option_name => $option_val){

	   $select='';

	   // if($option_name === 'Defualt Template'){

		  

		   // $select="selected" ;

		   

		   // }

	   

  echo '<option  name="' . esc_html($option_name) . '"  vlaue="' . esc_html($option_name) . '" rows="5" cols="60"' .$select .'>', esc_html($option_name) . '</option>';

   }

   

   echo '  

  </select>

   <input type="button" value="Update Template"  id="updatetemplate" name="" class="button button-primary" style="display:none" >

   

   <input type="button" value="Delete Template"  id="deletetemplate" name="" class="button button-primary" style="display:none" >';

   

   echo '

   <input type="button" value="Save New Template"  id="savenewtemplate" name="" class="button button-primary" >

  

	<br />

	<br />';

	

	

	}

	

}