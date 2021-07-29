<?php

namespace Reviews\Foundation;

use Reviews\Foundation\Functions;

use Reviews\Database\Query;

class Setting

{

	public static $settings = [];

	public static function add($plugin, $settings) 

	{

		static::$settings[$plugin] = $settings;

	}

	public static function boot() 

	{

		foreach (static::$settings as $plugin => $settings) {				

			$field = $plugin .'-settings';

			register_setting($plugin, $field);	

						 

			add_settings_section(

				$plugin,

				'Settings',

				[__CLASS__, 'section'],												

				$field													

				);

				

			foreach ($settings as $setting => $options) {	

			if(!empty($options['class'])=="hidden"){	

			$labelname= "<div class='hidden'>".$options['name']."</div>";				

			}else if(!empty($options['field'])=="labels"){

			$labelname= "<div class='mailchimp-lables'>".$options['name']."</div>";

				

			}else{	

			$labelname="<div>".$options['name']."</div>";				

			}

				add_settings_field(

					$setting,

					$labelname,

					[__CLASS__, 'setting'],

					$field,

					$plugin,									

					[$field, $setting, $options,$settings]

					);				

			}

		}

	}


	public static function setting($args) 

	{

	    

		$result=''; 

		

		list($field, $setting, $options,$settings) = $args;

			

		$values = get_option($field);

		if(!isset($values[$setting])){

			$values[$setting] = '';

		}

		$alloptions[]=$options['name'];

		if ( ! isset($options['field'])) {

			$options['field'] = 'text';

		}

		if ($options['field'] == 'text') {

		$ColorpickerField= array(				

		'reviews-email-template-settings[email-heading-color]',				'reviews-email-template-settings[email-footer-color]',				'reviews-email-template-settings[email-table-row-color]','reviews-email-template-settings[email-table-content-color]','reviews-email-template-settings[email-table-bgcolor]','reviews-email-template-settings[email-table-label-color]','reviews-email-template-settings[email-container-bgcolor]',	);						

		if(in_array($field .'['. $setting .']',$ColorpickerField))				

		{				

	$result .=  '<input class="colopickersetting" type="text" name="'. $field .'['. $setting .']" value="'. $values[$setting] .'" style="width: 390px">';	

	}

	

	

	elseif($field .'['. $setting .']' == 'reviews-activationkey-settings[reviews-activation_key]')

	{

		

		

	$result .= '<input type="text" name="'. $field .'['. $setting .']" value="'. $values[$setting] .'" style="width: 390px">';	

		

		

		$selectOptions=get_option('reviews-activationkey-settings'); 

					

				//match the keys and update the value of setting

				if(empty($selectOptions['activation'])){

					 $options = array( 

					'reviews-activation_key'=>$values[$setting], 

					 'activation'=>'false',

					 'activation_key'=>'85545eee520bb172025db97b266cbc7afe7f6c1c'

					 );

					 

					

					add_option('reviews-activationkey-settings', $options);

				

				

					} 					

				if(!empty($selectOptions['reviews-activation_key']) &&  $values[$setting]  == $selectOptions['activation_key']) 

				{

					

					

					$options = array( 

					'reviews-activation_key'=>$values[$setting],

					'activation'=>'true',

					'activation_key'=>'85545eee520bb172025db97b266cbc7afe7f6c1c'

					);	

				

				  

					update_option( 'reviews-activationkey-settings', $options );

					

				  print('<style>#activation_form{display:none;}</style>');

				   print('<script>window.location.href="admin.php?page=reviews"</script>');

						 

				 }

					

		  

	}elseif ($field .'['. $setting .']' == 'reviews-activationkey-settings[activation_key]')	

		 {		

			

		

			$result .=  '<input type="text"  class="hidden" name="'. $field .'['. $setting .']" value="'. $values[$setting] .'" style="width: 390px">';

			

		 

			 

		 }elseif ($field .'['. $setting .']' == 'reviews-activationkey-settings[activation]')	

		 {		

			

			$result .=  '<input type="text"  class="hidden" name="'. $field .'['. $setting .']" value="'. $values[$setting] .'" style="width: 390px">';

			

		 

			 

		}else{ 

	$result .=  '<input type="text" name="'. $field .'['. $setting .']" value="'. $values[$setting] .'" style="width: 390px">';

			

			

			

		

			

		}

	

	

		}elseif ($options['field'] == 'textarea') {

			echo '<textarea name="'. $field .'['. $setting .']" rows="5" cols="60">'. $values[$setting] .'</textarea>';

		}

		 elseif ($options['field'] == 'select') 

		 {

			

			if($field .'['. $setting .']' =='reviews-reviews-settings[overall_rating_label]')

				

		{				

				$selectOptions=get_option('reviews-reviews-settings');

				$my_options = array(

				'redirect_page'=>'',

				'homepage_tooltip'=>'',

				'branchpage_tooltip'=>'',

				'notification_emails_wa'=>'',

				'overall_rating_label'=>'1 Month'

				);									

				if(empty($selectOptions['overall_rating_label'])){

				update_option( 'reviews-reviews-settings', $my_options );					

				}

				$list= array('1 Month','3 Months','6 Months','1 Year');

		

					$result .=  '<select name="'. $field .'['. $setting .']">';

					

					foreach($list as $listkey){

						

						$checked = " ";

						if ($listkey == $selectOptions['overall_rating_label']) {

						$checked = 'selected';

						}

					

					$result .=  '<option name="'.$listkey.'" rows="5" cols="60" value="'.$listkey.'"  '.$checked.'  >'.$listkey.'</option>';

					}

					$result .= '</select>';




		 echo $result;

 

 
			}}