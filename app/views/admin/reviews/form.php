<?php
use Reviews\Foundation\Config;
use Reviews\Foundation\Validator;
use Reviews\Foundation\Functions;
use Reviews\Application\Reviews\Models\Review;
if(!is_admin()){
$formoptions = get_option( 'reviews-form-settings' ); 
} 
	
?>
<input type="hidden" name="region" value="<?php if(!empty($review)){ $review->region ?: $a['region']; } ?>">
<fieldset class="fieldset feildset--contact-details" >
	
		<div class="half-col-play">
		<div class="form-col half-form-col">
			<label for="author"><?php if (is_admin()){ echo 'First Name';}?></label>
			<input placeholder="<?php if(!empty($formoptions['fname'])){echo $formoptions['fname'];}else{echo "Enter Your First Name * ";}
			?>" type="text" name="authorfname" id="authorfname" value="<?= esc_html(Validator::old('authorfname', $review->authorfname)) ?>" required style="background:<?php echo $formoptions['inputbox-color']; ?>;  border: 1px solid <?php echo $formoptions['forminputboder-color']; ?>">
			<?php if (Validator::hasError('authorfname')): ?>
			<span class="error"><?= Validator::get('authorfname') ?></span>
			<?php endif; ?>
			
		</div>
		<div class="form-col half-form-col">
			<label for="author"><?php if (is_admin()){ echo 'Last Name';}?> </label>
			<input placeholder="<?php if(!empty($formoptions['lname'])){echo $formoptions['lname'];}else{echo "Enter Your Last Name ";}
			 ?>" type="text" name="authorlname" id="authorlname" value="<?= esc_html(Validator::old('authorlname', $review->authorlname)) ?>" required style="background:<?php echo $formoptions['inputbox-color']; ?>; border: 1px solid <?php echo $formoptions['forminputboder-color']; ?>">
			<?php if (Validator::hasError('authorlname')): ?>
			<span class="error"><?= Validator::get('authorlname') ?></span>
			<?php endif; ?>
		</div>
		
		<div class="form-col half-form-col">
			<label for="author"><?php if (is_admin()){ echo 'State/Suburb';}?> </label>
			<input placeholder="<?php if(!empty($formoptions['state'])){echo $formoptions['state'];}else{echo "Enter Your State/Suburb *";}
			?>" type="text" name="state" id="state" value="<?= esc_html(Validator::old('state', $review->state)) ?>" required style="background:<?php echo $formoptions['inputbox-color']; ?>; border: 1px solid <?php echo $formoptions['forminputboder-color']; ?>">
			<?php if (Validator::hasError('state')): ?>
			<span class="error"><?= Validator::get('state') ?></span>
			<?php endif; ?>
		</div>
		
		<div class="form-col half-form-col">
			<label for="email"><?php if (is_admin()){ echo 'Email';}?></label>
			<input placeholder="<?php if(!empty($formoptions['femail'])){echo $formoptions['femail'];}else{echo "Enter Your Email *";} ?>" type="email" name="email" id="email" value="<?= esc_html(Validator::old('email', $review->email))?>" required style="background:<?php echo $formoptions['inputbox-color']; ?>; border: 1px solid <?php echo $formoptions['forminputboder-color']; ?>">
			<?php if (Validator::hasError('email')): ?>
			<span class="error"><?= Validator::get('email') ?></span>
			<?php endif; ?>
		</div>
		<div class="form-col half-form-col">
			<label for="phone"><?php if (is_admin()){ echo 'Phone No';}?></label>
			<input placeholder="<?php if(!empty($formoptions['fphone'])){ echo $formoptions['fphone'];}else{echo "Enter Your Phone No";} ?>" type="text" name="phone" id="phone" value="<?= esc_html(Validator::old('phone', $review->phone)) ?>" required style="background:<?php echo $formoptions['inputbox-color']; ?>; border: 1px solid <?php echo $formoptions['forminputboder-color']; ?>">
			<?php if (Validator::hasError('phone')): ?>
			<span class="error"><?= Validator::get('phone') ?></span>
			<?php endif; ?>
		</div>
	 <!--legend></legend-->
		<!--div class="form-col">
		<!-?php
			$branches = Config::get('reviews.branches');
		?>
		<label for="branch">Branch Visited</label>
		<select id="branch" name="branch" required >
			<option value="">Branch Visited *</option>
			<!--?php foreach ($branches[$review->region ?: $a['region']] as $branch): ?>
			<option <!--?= $branch == Validator::old('branch', $review->branch) ? 'selected' : '' ?>>
				<!--?= $branch ?>
			</option>
			<!--?php endforeach; ?>
		</select>
		<!--?php if (Validator::hasError('branch')): ?>
		<span class="error"><!--?= Validator::get('branch') ?></span>
		<!--?php endif; ?>
	</div-->
	<div class="form-col half-form-col">	
	<?php	$rating = [	'5' => '5 Stars',	
						'4' => '4 Stars',			
						'3' => '3 Stars',					
						'2' => '2 Stars',					
						'1' => '1 Star'			
						]			
						?>			
			<label for="rating"><?php if (is_admin()){ echo 'Rating';}?></label>			
			<select id="rating" name="rating" style="background:<?php echo $formoptions['inputbox-color']; ?>; border: 1px solid <?php echo $formoptions['forminputboder-color']; ?>!important;" required >			
			<option value="" >Rating *</option>				
			<?php foreach ($rating as $value => $text): ?>					<option value="<?= $value ?>" <?= $value == Validator::old('rating', $review->rating) ? 'selected' : '' ?>><?= $text ?></option>				<?php endforeach; ?>			</select>			
			<?php if (Validator::hasError('rating')): ?>			
			<span class="error"><?= Validator::get('rating') ?></span>			
			<?php endif; ?>		
			</div>
	<?php
		$choices = ['Please select', 'Yes', 'No', 'Maybe'];
		
		?>
	</div>
	
	<div class="form-row">
		<div class="form-col">
			<label for="recommend">Would you recommend <?php echo bloginfo();?> to your family and friends?</label>
			<select id="recommend" name="recommend"  style="background:<?php echo $formoptions['inputbox-color']; ?>; border: 1px solid <?php echo $formoptions['forminputboder-color']; ?> !important;"required>
			
				<option value="">Would you recommend <?php echo bloginfo();?> to your family and friends? *</option>
				<option <?= 'Yes' == Validator::old('recommend', $review->recommend) ? 'selected' : ''?> >Yes</option>
				<option <?= 'No' == Validator::old('recommend', $review->recommend) ? 'selected' : '' ?> >No</option>
			</select>
		</div>
	</div>
 
	<div class="form-row"> 
		<div class="form-col">
			<label for="content"><?php if (is_admin()){ echo 'Feedback';}?></label>
			<textarea placeholder="<?php if(!empty($formoptions['feedback'])){echo $formoptions['feedback'];}else{ echo 'Enter your Feedback';}			?>" id="content" name="content" cols="20" rows="5" style="background:<?php echo $formoptions['inputbox-color']; ?>; border: 1px solid <?php echo $formoptions['forminputboder-color']; ?>;width:<?php if (!is_admin()){ echo '100% !important';}?>"><?= esc_html(stripslashes(Validator::old('content', $review->content)))?></textarea>
			<?php if (Validator::hasError('content')): ?>
			<span class="error"><?= Validator::get('content') ?></span>
			<?php endif; ?>
		</div>
		
	</div>	
	<div class="form-row"> 
		<div class="form-col">
		<input type="hidden" value="<?php  Functions::get_client_ip()?>" name="userip">
		</div>
	</div>			
	<?php if (is_admin()): ?>
	<div class="form-row">
		<div class="form-col">
		<?php
			$statusList = [Review::STATUS_PENDING, Review::STATUS_PUBLISHED, Review::STATUS_DECLINED];
		?>
		<label for="status">Status</label>
		<select id="status" name="status">
		<?php foreach ($statusList as $status): ?>
			<option <?= $status == Validator::old('status', !empty($review->status)) ? 'selected' : '' ?>><?= $status ?></option>
		<?php endforeach; ?>
		</select>
		</div>
	</div>
	<?php endif; ?>
	<?php if ( ! is_admin()): ?>
	<div class="form-row form-row--note">
		<div class="form-col">
			<p class="mandatory-fields">* Mandatory fields</p>
		</div>
	</div>
	<?php endif; ?>
</fieldset>
