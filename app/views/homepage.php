<?php
	$option = get_option('reviews-reviews-settings');	
	$option = get_option('reviews-reviews-settings');	
	if(!empty($option['overall_rating_label'])){	
	$month=$option['overall_rating_label'];}	
	else{$month='1 Month';}
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
<?php 
if (!empty($reviews)){
?>
<div class="col testimonial-slide">

	<ul class="bxslider"> 

	<?php 	if ($reviews): ?>


		<?php 
	
		
		
		foreach ($reviews as $review): ?>



		<?php $av_stars = floor($review->rating * 2) / 2;

					$av_stars = str_replace(".","-", $av_stars);

		 ?>



		<li class="testimonial-slide-item">

			<div class="testimonial-slide-speech">

				<div class="testimonial-slide-speech-text">

				<?php /* <?= //nl2br($review->content) */ ?>

				<?php $testi_content = nl2br($review->content);

					if (strlen($testi_content) >= 180) {

						echo substr($testi_content, 0, 180). " ... ";

					}

					else {

						echo $testi_content;

					}

					?>

				</div>			

			</div>

			<span class="testimonial-slide-author">

				<strong><?= $review->authorfname."&nbsp;".$review->authorlname ?></strong>, <!--?= $review->state ?-->  <span class="testimonial-region"><?= $review->state ?></span> - <?= date('d M Y', strtotime($review->created_at)) ?><br>

				<span class="testimonial-stars testimonial-stars-<?php echo $av_stars; ?>"><span><?= $review->rating ?> Stars</span></span>

			</span>

		</li>

		<?php endforeach; ?>

	<?php endif; ?>

	</ul>

</div>
<?php }?>


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
