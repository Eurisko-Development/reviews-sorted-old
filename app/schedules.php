<?php
use Mark\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Reviews\Application\Reviews\Models\Review;
use Reviews\Foundation\Installer;
use Reviews\Application\Reviews\Requests\Review as ReviewRequest;

function isa_add_cron_recurrence_interval( $schedules ) {
    $schedules['every_three_minutes'] = array(
        'interval'  => 180,
        'display'   => __( 'Every 3 Minutes', 'textdomain' )
    );
    $schedules['every_fifteen_minutes'] = array(
        'interval'  => 900,
        'display'   => __( 'Every 15 Minutes', 'textdomain' )
    );
	$schedules['eurisko_daily'] = array(
        'interval'  => 43200,
        'display'   => __( 'Twice Daily (Custom)', 'textdomain' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'isa_add_cron_recurrence_interval' );

Installer::bootde(function(){
    wp_clear_scheduled_hook("reviews_monthly_reports_scheduler_hook");
    wp_clear_scheduled_hook("reviews_daily_send_email_scheduler_hook");
});

Installer::boot(function()
{
	
	
    if ( ! wp_next_scheduled( 'reviews_monthly_reports_scheduler_hook' ) ) {
//        wp_schedule_event( time(), 'daily', 'reviews_monthly_reports_scheduler_hook' );
        wp_schedule_event(time(), 'daily', 'reviews_monthly_reports_scheduler_hook' );
    }
	
	
});

if ( ! wp_next_scheduled ( 'reviews_daily_send_email_scheduler_hook' )) {
	wp_schedule_event( time(), 'daily', 'reviews_daily_send_email_scheduler_hook' );
}

add_action( 'reviews_daily_send_email_scheduler_hook', 'reviews_daily_send_email_scheduler_run_cron' );
function reviews_daily_send_email_scheduler_run_cron($a=1) {
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$date = new DateTime();
	$date->setTimezone(new DateTimeZone('Australia/Perth'));
	
	wp_mail( 'phongtran255@gmail.com', 'Email scheduler Cron Run', 'Date: ' . $date->format('d/m/Y H:i'), $headers, array( '' ) );
	
	$templates = get_option('reviews_popup_email_template'); 
	
	global $wpdb;
	$table_reviews = "{$wpdb->prefix}reviews";
	$table_name    = "{$wpdb->prefix}email_schedule";
	$query_date    = $date->format('Y-m-d');
	$results       = $wpdb->get_results( $wpdb->prepare(
		"
			SELECT s.*, r.email, r.authorfname, r.rating, r.content, r.authorlname    
			FROM {$table_name} s
			INNER JOIN {$table_reviews} r ON r.id = s.review_id 
			WHERE s.date_send = %s AND s.status = %d
		",
		$query_date,
		0
	) );
	if($results):
		foreach($results as $row){
			$subject = $subject = $row->email_template;
			$message = isset($templates[$subject]) ? $templates[$subject] : '';
			if(empty($message)){
				continue;
			}
			$message = apply_filters( 'the_content', $message); 
			$message = str_replace(
				array('*|FNAME|*', '*|LNAME|*', '*|STARRATING|*', '*|FEEDBACK|*', '*|EMAIL|*'),
				array(
					$review->authorfname,
					$review->authorlname,
					$review->rating,
					$review->content,
					$review->email,
				),
				$message
			);
					
			$to = $row->email; // 'phongtran255@gmail.com'; // 
					
			$sent = wp_mail( $to, $subject, $message, $headers, array( '' ) );
			$wpdb->query( $wpdb->prepare( 
				"
					UPDATE {$table_name}
					SET status = %d
					WHERE ID = %d
				",
				1,
				intval($row->ID)
			) );
		}
	endif;
}

add_action('reviews_monthly_reports_scheduler_hook', 'reviews_monthly_reports_scheduler');
function reviews_monthly_reports_scheduler($a=1) {
    $date = date('d');
    if ('01' == $date ) {
            $startDate = strtotime('-1 month', strtotime('first day of'));
            $from = date('Y-m-d', $startDate) . ' 00:00:00';
            $to = date('Y-m-d', strtotime('last day of', $startDate)) . ' 23:59:59';
            $settings = get_option('reviews-reviews-settings');
            $emails = explode("\r\n", $settings['notification_emails_wa']);
            $filename = sprintf('Reviews for %s.xlsx', date('F', $startDate));
            $filepath = sprintf('%s/%s', __DIR__, $filename);
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);
            foreach (['wa', 'sa'] as $index => $region) {
                $sheet = $spreadsheet->createSheet($index);
                $sheet->setTitle(sprintf('Reviews (%s)', strtoupper($region)));
                $reviews = Review::whereBetween('created_at', [$from, $to])
                    ->where('region', $region)
                    ->get();
                foreach ([
                             'Date', 'Customer', 'Email', 'Phone', 'Branch', 'Status', 'Rating', 'Recommend', 'Content'
                         ] as $idx => $field) {
                    $sheet->setCellValue(chr(65 + $idx) . '1', $field);
                }
                foreach ($reviews as $idx => $review) {
                    $date = new DateTime($review->created_at);
                    $date->setTimezone(new DateTimeZone('Australia/Perth'));
                    $sheet->setCellValue('A' . (3 + $idx), $date->format('d/m/Y H:i'));
                    $sheet->setCellValue('B' . (3 + $idx), $review->author);
                    $sheet->setCellValue('C' . (3 + $idx), $review->email);
                    $sheet->setCellValue('D' . (3 + $idx), $review->phone);
                    $sheet->setCellValue('E' . (3 + $idx), $review->branch);
                    $sheet->setCellValue('F' . (3 + $idx), $review->status);
                    $sheet->setCellValue('G' . (3 + $idx), $review->rating);
                    $sheet->setCellValue('H' . (3 + $idx), $review->questionnaire['recommend']);
                    $sheet->setCellValue('I' . (3 + $idx), $review->content);
                }
            }
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filepath);
            if (!function_exists('wp_mail')) {
                include_once ABSPATH . '/wp-includes/pluggable.php';
            }
			$mailtitle=get_bloginfo('name'). '-Feedback   ';
            wp_mail(
                $emails,
                'Monthly Reviews Report',
                'This mail is being sent to all these emails '.implode(',',$emails) ,
                [
                    'Content-Type: text/html; charset=UTF-8',
                    'From:'.$mailtitle,
                    'Cc: deploy@eurisko.com.au'
                ],
                [
                    $filepath
                ]
            );
            @unlink($filepath);
        }
}