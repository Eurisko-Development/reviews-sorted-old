<?php
namespace Reviews\Application\Reviews\Controllers;
use Reviews\Database\Query;
use Reviews\Foundation\View;
use Reviews\Foundation\Validator;
use Reviews\Application\Reviews\Models\Review;
use Reviews\Application\Reviews\Requests\Review as ReviewRequest;
use Reviews\Foundation\Functions;
class FeedbackController
{
	public static function average($attributes, $content = null)
	{
		$a = shortcode_atts( array(
	        'min' => 3,
	        'limit' => 10
	    ), $attributes);
	    $rating = [];
			if (isset($a['rating'])) { $rating = explode(',', $a['rating']); }
			if (!is_array($rating)) $rating = [$rating];
	    
	    $months = sizeof(Review::groupBy('YEAR(created_at)')
	    				->groupBy('MONTH(created_at)')
	    				->get());
	    //$average = Review::select('AVG(rating) AS average')->first()->average;
	    $week = Review::select('COUNT(*) AS c, YEAR(created_at) AS year, MONTH(created_at) AS week,created_at')
	    			  ->whereIn('rating', $rating)
	    			  ->where('status', 'Published')
	    			  ->groupBy('WEEK(created_at)')
	    			  ->groupBy('YEAR(created_at)')
	    			  ->latest()
	    			  ->having('c >= '. $a['min'])
	    			  ->get();
	    $review_total = 0;
	    $review_end_date = 0;
	    foreach ($week as $key => $new_reviews) {
	    	//We get the end dates where we can collect atleast 10 reviews
	    	$review_end_date = $new_reviews->created_at;
	    	$review_total    += $new_reviews->c;
	    	if( $review_total >= $a['limit'] ){
	    		break; //we got enough reviews by this time
	    	}
	    }
			$settingsOption=get_option('reviews-reviews-settings');
			
			$selectoption=$settingsOption['overall_rating_label'];
			
			if($selectoption=='1 Month')
			{
				   $date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-1 Month",$date) );
			}else if($selectoption=='3 Months'){
				
					$date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-3 Month",$date) );
			}
			else if($selectoption=='6 Months'){
				
				 $date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-6 Month",$date) );
			}
			else if($selectoption =='1 Year'){
				
				   $date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-1 Year",$date) );
			}
			$total = Review::select('COUNT(*) AS c')	
			->where('created_at', '>=',$review_start_date)		
			->first()->c;	 
			
			$average = Review::select('AVG(rating) AS average' )
			->where('created_at', '>=',$review_start_date)
			->first()->average;
			 
		
	
	    $_View = new View();
		return $_View->render('average', compact('total', 'months', 'average'));
	}
	
	public static function slider($attributes, $content = null)
		{
			
			
			$a = shortcode_atts( array(
				'min' => 3,
				'rating' => '5',
				'limit' => 10
			), $attributes);
			$rating = explode(',', $a['rating']);
			if (!is_array($rating)) $rating = [$rating];
			$total = Review::count();
			$months = sizeof(Review::groupBy('YEAR(created_at)')
							->groupBy('MONTH(created_at)')
							->get());
			
			$week = Review::select('COUNT(*) AS c, YEAR(created_at) AS year, MONTH(created_at) AS MONTH,created_at')
						  ->whereIn('rating', $rating)
						  ->where('status', 'Published')
						  ->groupBy('MONTH(created_at)')
						  ->groupBy('YEAR(created_at)')
						  ->get();
			$review_total = 0;
			$review_end_date = 0;
			foreach ($week as $key => $new_reviews) {
				//We get the end dates where we can collect atleast 10 reviews
				$review_end_date = $new_reviews->created_at;
				$review_total    += $new_reviews->c;
				
			}
			
			$settingsOption=get_option('reviews-reviews-settings');
			
			$selectoption=$settingsOption['overall_rating_label'];
			
			if($selectoption=='1 Month')
			{
				   $date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-1 Month",$date) );
			}else if($selectoption=='3 Months'){
				
					$date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-3 Month",$date) );
			}
			else if($selectoption=='6 Months'){
				
				 $date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-6 Month",$date) );
			}
			else if($selectoption=='1 Year'){
				
				   $date=strtotime($review_end_date);
				   $review_start_date=date("Y-m-d", strtotime("-1 Year",$date) );
			}
				 
					
			 $average = Review::select('AVG(rating) AS average' )
			->where('created_at', '>=',$review_start_date)
			->first()->average;
			
						$reviews = Review::where(Query::raw('created_at'),'>=', $review_start_date)
						->where('status', 'Published')
						->take($a['limit'])
						->rand()
						->get();
								 
		$_View = new View();
		return $_View->render('slider', compact('reviews'));
		}

	public static function showForm($attributes, $content = null)
	{
		$a = shortcode_atts( array(
	        'region' => 'wa'
	    ), $attributes);
		$_View = new View();
		return $_View->render('form', compact('a'));
	}

	public static function submitReview()
	{   
	    
		if ( ! Validator::validate(new ReviewRequest)) {
			wp_safe_redirect(wp_get_referer());
			exit;
		}
		foreach ($_REQUEST as &$param) {
			$param = stripslashes($param);
		}
		
	
		$review = new Review($_REQUEST);
		
		$review->status = Review::STATUS_PENDING;
		$review->save();
	
		$data = [
		'email'     => $_POST['email'],
		'status'    => 'subscribed',
		'firstname' => $_POST['authorfname'],
		'lastname'  => $_POST['authorlname'],
		'starrating'  =>$_POST['rating'],
		'recommend'	 => strtoupper($_POST['recommend']),
		'feedback'	 => $_POST['content']
		];							
		$mailarray = array('5','4'); 			
	 
		 static::sendAutoResponder($review);
		 static::sendToHeadOffice($review);
	
		$settings = get_option('reviews-reviews-settings');	
		// Added custom do action by WpWeb
		do_action( 'reviews_after_submit_review', $review );
		wp_safe_redirect( $settings['redirect_page'] ?: home_url() );
		exit;
	}

	public static function sendAutoResponder(Review $review)
	{
		$sitetitle=get_bloginfo('name');
		$headers = array(
		
		'Content-Type: text/html; charset=UTF-8',
		);
		$mailResult = false;
		$mailResult = wp_mail( $review->email, 'Thank you for your review on your recent      '. get_bloginfo('name').'  experience',View::render('mail.responder', compact('review', 'hide_approval_links')), $headers);

	}

	public static function sendToHeadOffice(Review $review)
	{
		$settings = get_option('reviews-reviews-settings');
		require_once __DIR__ .'/../../key.php';
	
	
		
		$emails = explode("\r\n", $settings['notification_emails_wa']);
	
		$star = $review->rating == 1 ? 'Star' : 'Stars';
		$subject = sprintf('New Customer Feedback %s %s', $review->rating, $star);
	
		$mailtitle=get_bloginfo('name'). 'Feedback   ';
		$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		
		);
		$_View = new View();
		$mailResult = false;
		$mailResult = wp_mail( $emails, $subject, $_View->render('mail.head', compact('review', 'hide_approval_links')), $headers);
		
		
	}

	
	
}
