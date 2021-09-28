<?php
	$option = get_option('reviews-reviews-settings');	if(!empty($option['overall_rating_label'])){	$month=$option['overall_rating_label'];}	else{$month='1 Month';}
?>
<div class="col testimonial-rating">
	<h2>Our customers give <?php echo bloginfo();?> </h2>
	<?php $av_stars = floor($average * 2) / 2;
				$av_stars = str_replace(".","-", $av_stars);
	 ?>
	<div class="testimonial-stars testimonial-stars-<?php echo $av_stars; ?>"><span> an average star rating of <?= $average ?> </span></div>
	<span class="testimonial-rating-desc">Based on <strong><?= number_format($total) ?></strong> reviews over the last <?= $month ?> . <span class="testimonial-tooltip tooltip" data-tooltip-content="#testimonial-tooltip-content">? 

		<span id="testimonial-tooltip-content">
		  <?= $option['homepage_tooltip'] ?>
		</span>
	
	</span></span><br>
	<span class="powered_reviews">Powered by <a href="http://www.reviewssorted.com">Reviews Sorted</a> </span>
</div>

<script type="application/ld+json">
    {
        "@context": "http://schema.org",
        "@type": "LocalBusiness",
        "name": "Reviews",
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?php echo $average; ?>",
            "ratingCount": "<?php echo $total; ?>"
        },
		"image": "<?php echo get_site_icon_url();?>",
        "telephone": "",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "",
            "addressRegion": "",
            "postalCode": "",
            "streetAddress": ""
        }
    }
</script>
