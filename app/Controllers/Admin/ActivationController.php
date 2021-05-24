<?php
namespace Reviews\Application\Reviews\Controllers\Admin;
use Reviews\Foundation\View;
class ActivationController
{
	public static function index() 
	{
		$_View = new View();

		return $_View->render('admin.settings.activation', array(), true);
	}
}