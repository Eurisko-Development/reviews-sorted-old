<?php
namespace Reviews\Application\Reviews\Requests;
use Reviews\Contracts\Request;
class Review implements Request
{
	public function handle()
	{
		return [
			'authorfname' => 'required',
			'authorlname' => 'required',
			'phone' => 'required',
			'email' => 'required',
			'rating' => 'required'
		];
	}
}
