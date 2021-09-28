<?php



namespace Reviews\Application\Reviews\Controllers\Admin;



use Reviews\Foundation\View;



class ReportController


{

public static function index() 


{
	@View::render('admin.settings.report', array(), true);



}


}