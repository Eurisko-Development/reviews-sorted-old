<?php
	$option = get_option('reviews-reviews-settings');
?>
<div class="col testimonial-slide">
	<ul class="bxslider"> 
	<?php 	if ($reviews): ?>
		<?php 
	
		
		
		foreach ($reviews as $review): ?>
		<?php $av_stars = floor($review->rating * 2) / 2;
					$av_stars = str_replace(".","-", $av_stars);
		 ?>
		<li class="testimonial-slide-item" itemscope itemtype="https://schema.org/Review">
			<div>
				<div class="testimonial-slide-speech" itemprop="itemReviewed" itemscope itemtype="https://schema.org/LocalBusiness">
					<div class="testimonial-slide-speech-text" itemprop="reviewBody">
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
					
					<?php $settings = get_option('reviews-reviews-settings'); ?>
					<meta itemprop="name" content="<?php bloginfo('name'); ?>">	
					<meta itemprop="image" content="<?php echo get_site_icon_url();?>">	
					<meta itemprop="address" content="<?php echo $settings['business_address'] ?>">	
					<meta itemprop="telephone" content="<?php echo $settings['business_phone'] ?>">	
					<meta itemprop="priceRange" content="$$$">					
				</div>
				<span class="testimonial-slide-author" itemprop="author" itemscope itemtype="https://schema.org/Person">
					<strong itemprop="name"><?= $review->authorfname."&nbsp;".$review->authorlname ?></strong>, <!--?= $review->state ?-->
					<span class="testimonial-region">
						<?= $review->state ?></span> - <?= date('d M Y', strtotime($review->created_at)) ?><br>
					<span class="testimonial-stars testimonial-stars-<?php echo $av_stars; ?>" itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
						<span><?= $review->rating ?> Stars</span>
						<meta itemprop="ratingValue" content="<?php echo $av_stars; ?>">	
						<meta itemprop="bestRating" content="5">	
					</span>
				</span>
			</div>
		</li>
		<?php endforeach; ?>
	<?php endif; ?>
	</ul>
</div>
