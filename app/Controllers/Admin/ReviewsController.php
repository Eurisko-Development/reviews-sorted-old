<?php

namespace Reviews\Application\Reviews\Controllers\Admin;

use Reviews\Foundation\View;
use Reviews\Foundation\Validator;
use Reviews\Foundation\FlashMessage;
use Reviews\Application\Reviews\Models\Review;
use Reviews\Application\Reviews\Requests\Review as ReviewRequest;

class ReviewsController
{
	public static function index() 
	{
		$limit = 20;

		$query = Review::query();

		if (isset($_GET['filter-region']) && $_GET['filter-region']) {
			$query->where('region', $_GET['filter-region']);
		}
		if (isset($_GET['filter-branch']) && $_GET['filter-branch']) {
			$query->where('branch', $_GET['filter-branch']);
		}

		$total = $query->count();
		$reviews = $query->latest()->paginate($limit);
		$pages = ceil($total / $limit);

		@View::render('admin.reviews.index', compact('reviews', 'pages'), true);
	}

	public static function edit() 
	{
		$review = Review::find((int) $_REQUEST['id']);

		@View::render('admin.reviews.edit', compact('review'), true);
	}

	public static function update() 
	{
		if ( ! Validator::validate(new ReviewRequest)) {
			wp_safe_redirect(wp_get_referer());
		}

		foreach ($_REQUEST as &$param) {
			$param = stripslashes($param);
		}
		
		$review = Review::find((int) $_REQUEST['id']);
		$review->fill($_REQUEST);
		$review->status = $_REQUEST['status'];
		$review->save();

		@FlashMessage::success('Changes has been successfully saved.');

		return wp_redirect(admin_url('admin.php?page=reviews'));
	}

	public static function status() 
	{
		$id = (int) $_REQUEST['id'];
		$hash = $_REQUEST['hash'];
		$status = $_REQUEST['status'];

		$review = Review::find($id);

		if ($hash != sha1(WH_REVIEW_KEY . $id . home_url())) {
			throw new \Exception('Invalid data');
		}

		$review->status = $status;
		$review->save();

		@View::title('– ' . $status);
		@View::content('admin.reviews.status', compact('review'));
	}		public static function destroy() 
	{
		$id = (int) $_REQUEST['id'];
	}
	
	
	public static function delete() 
	{
		$review = Review::find((int) $_REQUEST['id']);
		@FlashMessage::success('Changes has been successfully saved.');
		@View::render('admin.reviews.delete', compact('review'), true);
	}


}