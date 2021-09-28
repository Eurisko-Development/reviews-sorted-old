<?php

namespace Reviews\Foundation\Validator;

class Required extends BaseValidator
{
	public static function validate($data, $field)
	{
		return isset($data[$field]) && !empty($data[$field]) ? true : _(static::fieldName($field) ." is required");
	}
}