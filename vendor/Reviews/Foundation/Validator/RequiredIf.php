<?php

namespace Reviews\Foundation\Validator;

class RequiredIf extends BaseValidator
{
	public static function validate($data, $field, $requiredField)
	{
		return (isset($data[$requiredField]) && !empty($data[$requiredField]) && isset($data[$field]) && !empty($data[$field])) || !isset($data[$requiredField]) ? true : _(self::fieldName($field) ." is required");
	}
}