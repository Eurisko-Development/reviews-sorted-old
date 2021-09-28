<?php

namespace Reviews\Foundation;

class FormSetting
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
				add_settings_field(
					$setting,
					$options['name'],
					[__CLASS__, 'setting'],
					$field,
					$plugin,
					[$field, $setting, $options]
				);
			}
		}
	}

	public static function section() 
	{
	}

	public static function setting($args) 
	{
		list($field, $setting, $options) = $args;
		
		$values = get_option($field);

		if ( ! isset($options['field'])) {
			$options['field'] = 'text';
		}

		if ($options['field'] == 'text') {	
		
		$ColorpickerField= array(
		'reviews-form-settings[inputbox-color]',
		'reviews-form-settings[form-color]',
		'reviews-form-settings[submitbutton-color]',				'reviews-form-settings[submitbutton-font-color]',
		'reviews-form-settings[formborder-color]',
		'reviews-form-settings[forminputboder-color]'
		);
		
		
		
			 if(in_array($field .'['. $setting .']',$ColorpickerField)){
			echo '<input class="colopickersetting" type="text" name="'. $field .'['. $setting .']" value="'. $values[$setting] .'" style="width: 390px">';
			 }else{
				 echo '<input  type="text" name="'. $field .'['. $setting .']" value="'. $values[$setting] .'" style="width: 390px">';
			 }
		}
		elseif ($options['field'] == 'textarea') {
			echo '<textarea name="'. $field .'['. $setting .']" rows="5" cols="60">'. $values[$setting] .'</textarea>';
		}
	}
}