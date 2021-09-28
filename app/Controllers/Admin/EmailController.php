<?php





namespace Reviews\Application\Reviews\Controllers\Admin;
use Reviews\Foundation\View;
class EmailController
{
	public static function index() 
	{
		@View::render('admin.email.index', array(), true);
	}	public static function template() 	{		@View::render('admin.email.template', array(), true);	}
}