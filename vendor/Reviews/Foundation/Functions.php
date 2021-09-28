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
	
	
		public function syncMailchimp()

		{
			     $selectOption=get_option('reviews-mailchimp-settings');

					$apiKey = $selectOption['mailchimpApikey'];
					$listId = $selectOption['mailchimpList'];
					$dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
					$args = array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( 'user:'. $apiKey )
					)
				);
		
				$response = wp_remote_get( 'https://'.$dataCenter.'.api.mailchimp.com/3.0/lists/', $args );
					
						$AllLists=json_decode($response['body'],true);
						
						return $AllLists;
		}		
		public function GetMailchimpMergeFields()
		{
			     $selectOption=get_option('reviews-mailchimp-settings');
			
					$apiKey = $selectOption['mailchimpApikey'];
					$listId = $selectOption['mailchimpList'];
					$dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
					$args = array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( 'user:'. $apiKey )
					)
				);
				 

				$response = wp_remote_get( 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/merge-fields/',$args ); 
				$AllLists=json_decode($response['body'],true);
				return $AllLists;
				
		}
	
			public static function InsertdataMailChimp($data)
		{
			
	
				$selectOption=get_option('reviews-mailchimp-settings');

				$apiKey = $selectOption['mailchimpApikey'];
				$listId = $selectOption['mailchimpList'];
			    $memberEmail = $selectOption['mailchimpemail'];
				$mailchimpfname = $selectOption['mailchimpfname'];
				$mailchimplname = $selectOption['mailchimplname'];
				$mailchimpstarrating = $selectOption['mailchimpstarrating'];
				$mailchimprecommend = $selectOption['mailchimprecommend'];
				$mailchimpfeedback = $selectOption['mailchimpfeedback'];
				
				 		
			
			
				
					switch ($memberEmail){
					case 'email_address':
					$email=$data['email'];
					break;
					case 'FNAME':
					$email=$data['firstname'];
					break;
					case 'LNAME':
					$email=$data['lasttname'];
					break;
					case 'STARRATING':
					$email=$data['starrating'];
					break;
					case 'RECOMMEND':
					$email=$data['recommend'];
					break;
					case 'FEEDBACK':
					$email=$data['feedback'];
					break;
					default:
					$email= $data['email'] ;
					break;
					}
					
					switch ($mailchimpfname){
					case 'email_address':
					$fname=$data['email'];
					break;
					case 'FNAME':
					$fname=$data['firstname'];
					break;
					case 'LNAME':
					$fname=$data['lastname'];
					break;
					case 'STARRATING':
					$fname=$data['starrating'];
					break;
					case 'RECOMMEND':
					$fname=$data['recommend'];
					break;
					case 'FEEDBACK':
					$fname=$data['feedback'];
					break;
					default:
					$fname=$data['firstname'];
					break;
					}
					
					
					switch ($mailchimpstarrating){
					case 'email_address':
					$starrating=$data['email'];
					break;
					case 'FNAME':
					$starrating=$data['firstname'];
					break;
					case 'LNAME':
					$starrating=$data['lastname'];
					break;
					case 'STARRATING':
					$starrating=$data['starrating'];
					break;
					case 'RECOMMEND':
					$starrating=$data['recommend'];
					break;
					case 'FEEDBACK':
					$starrating=$data['feedback'];
					break;
					default:
					$starrating=$data['starrating'] ;
					break;
					}
					
					
					switch ($mailchimplname){
					case 'email_address':
					$lname=$data['email'];
					break;
					case 'FNAME':
					$lname=$data['firstname'];
					break;
					case 'LNAME':
					$lname=$data['lastname'];
					break;
					case 'STARRATING':
					$lname=$data['starrating'];
					break;
					case 'RECOMMEND':
					$lname=$data['recommend'];
					break;
					case 'FEEDBACK':
					$lname=$data['feedback'];
					break;
					default:
					$lname=$data['lastname'];
					break;
					}
					
					
					switch ($mailchimprecommend){
					case 'email_address':
					$recommend=$data['email'];
					break;
					case 'FNAME':
					$recommend=$data['firstname'];
					break;
					case 'LNAME':
					$recommend=$data['lastname'];
					break;
					case 'STARRATING':
					$recommend=$data['starrating'];
					break;
					case 'RECOMMEND':
					$recommend=$data['recommend'];
					break;
					case 'FEEDBACK':
					$recommend=$data['feedback'];
					break;
					default:
					$recommend=$data['recommend'] ;
					break;
					}
					
					switch ($mailchimpfeedback){
					case 'email_address':
					$feedback=$data['email'];
					break;
					case 'FNAME':
					$feedback=$data['firstname'];
					break;
					case 'LNAME':
					$feedback=$data['lastname'];
					break;
					case 'STARRATING':
					$feedback=$data['starrating'];
					break;
					case 'RECOMMEND':
					$feedback=$data['recommend'];
					break;
					case 'FEEDBACK':
					$feedback=$data['feedback'];
					break;
					default:
					$feedback=$data['feedback'];
					break;
					}
					
				
					
				
				
				$dataCenter = substr($apiKey,strpos($apiKey,'-')+1);					
				 $args = array(				
				 'headers' => array(					
				 'Authorization' => 'Basic ' . base64_encode( 'user:'. $apiKey )			
				 )					
				 );	
				 
				 
				 $response = wp_remote_get( 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/merge-fields/',$args ); 
				$AllLists=json_decode($response['body'],true);
			
                $listfitter=sizeof($AllLists['merge_fields']);	
				$FiltterMergeField=array();
				
				foreach($AllLists as $kay => $mergefield)
				{		
						
					for($i=0; $i<=$listfitter; $i++)
					{
						
						if(@strlen($mergefield[$i]['tag']) != '1'){
						@$FiltterMergeField[$mergefield[$i]['tag']]=$mergefield[$i]['tag'];	
						}
					}
				
				}	
			
                      $listfitters=sizeof($selectOption);
                    foreach($selectOption as $key => $name)
                    {
                    	
                    	 if("mailchimpcustomUrl"== $key){
                    		    $customurl[]=$key;
                    		}
                    	
                    	for($i=1; $i<=$listfitters; $i++)
                    	{
                    	    
                    	  
                    		if("mailchimpcustomUrl".$i == $key)
                    		{
                    		    $customurl[]=$key;
                    		}
                    		
                    	}
                    }
					
					$json=array(
							'email_address' => $email,
							'status'        => $data['status'],
							
							'merge_fields'  =>array(
							'FNAME'     => $fname,
							'LNAME'     => $lname,
							'STARRATING' => $starrating,
							'RECOMMEND' => $recommend,
							'FEEDBACK' => $feedback,
							
							)
							);
							
					foreach($customurl as $keys => $url)
                        {
							  strlen($url);
							  $len=strlen($url);
                    	    
							 $value=$selectOption[$url];
							  if(strlen($url) != '1')
							  {
							$urlname=substr($url,15);
							$field_name=strtoupper($urlname);
							$jsons[$field_name]=array($field_name => $value);
							
							//array_push($json['merge_fields'],$jsons);
                    		
						}
						}
						
						
						
						foreach($jsons as $keys => $dta)
						{
						
						foreach($dta as $keyss =>$val){
						$json['merge_fields'][$keyss] = $val;
						@array_push($json['merge_fields'][$keyss],$val);	
						
						}
						
						}
						
						
                   $json_data=str_replace('\/', '/', json_encode($json));
                    	
						
							
								
				$memberId = md5(strtolower($data['email']));
				$dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
				$url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

				$ch = curl_init($url);

				curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);                                                                                         
				$result = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
						
				return $result;
				
		}
		
		public function AddMailchimpMergeFields()				 
		{					
				 $selectOption=get_option('reviews-mailchimp-settings');					
				 $apiKey = $selectOption['mailchimpApikey'];					
				 $listId = $selectOption['mailchimpList'];					
				 $Customname = $selectOption['mailchimpcustomfieldsdatalable'];					
				 $CustomType = $selectOption['mailchimpcustomfieldsdatatype'];			
				 $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);					
				 $args = array(				
				 'headers' => array(					
				 'Authorization' => 'Basic ' . base64_encode( 'user:'. $apiKey )			
				 )					
				 );	
				 
				 
				 $response = wp_remote_get( 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId .'/merge-fields/',$args ); 
				$AllLists=json_decode($response['body'],true);
			
                $listfitter=sizeof($AllLists['merge_fields']);	
				$FiltterMergeField=array();
				
				foreach($AllLists as $kay => $mergefield)
				{		
						
					for($i=0; $i<=$listfitter; $i++)
					{
						
						if(strlen($mergefield[$i]['tag']) != '1'){
						$FiltterMergeField[$mergefield[$i]['tag']]=$mergefield[$i]['tag'];	
						}
					}
				
				}	
			
                      $listfitters=sizeof($selectOption);
                    foreach($selectOption as $key => $name)
                    {
                    	
                    	 if("mailchimpcustomUrl"== $key){
                    		    $customurl[]=$key;
                    		}
                    	
                    	for($i=1; $i<=$listfitters; $i++)
                    	{
                    	    
                    	  
                    		if("mailchimpcustomUrl".$i == $key)
                    		{
                    		    $customurl[]=$key;
                    		}
                    		
                    	}
                    }
                        
                        
                        
                    
        
				  $url ='https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/merge-fields'; 					
				  $ch = curl_init($url);								
				  
				  foreach($customurl as $keys => $url)
                        {
                             strlen($url);
                    		if(strlen($url) != '1'){
                    		 
                    		$len=strlen($url);
                    	    $urlname=substr($url,15);
                    		
                    	
                    	   $json=json_encode([
                            'title'=>'Mailchimp fields Using Plugins',
                            "tag" => $urlname,	
                            "required" => false,
                            "name" => $urlname,	
                            "type" => 'url',
                            ]);	
                    		
                    		}
                    		    
                    		
                    		
                    		
                    		
                    	
                   curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);	
				  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);					
				  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);				
				  curl_setopt($ch, CURLOPT_TIMEOUT, 10);				
				  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');		
				  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);		
				  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);	
				  
                    
                    if(!in_array(strtoupper($urlname),$FiltterMergeField)){    
                    $result = curl_exec($ch);			
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);	
  		            return  $result;
                    }
			
                        
                  
                }
                            
                        
                   			
				
                        
                        
		
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