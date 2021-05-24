<?php

use Reviews\Foundation\View;

$formoptions = get_option( 'reviews-form-settings' ); if(!is_admin()){$formoptions = get_option( 'reviews-form-settings' ); } 



$_View = new View();

?>

	<?php /*<h2 class="form-heading">Customer Reviews - Feedback</h2>*/ ?>

	<div class="cr-form-wrap" style="background:<?php echo $formoptions['form-color']; ?>; border: 1px solid<?php echo $formoptions['formborder-color']; ?>">			

	

	<div class="head-strip">			

	<h4><?php if(!empty($formoptions['fheading'])){echo $formoptions['fheading'];}else{echo "Submit your feedback!";}?></h4>			

	<p> <?php if(!empty($formoptions['fsubheading'])){echo $formoptions['fsubheading'];}else{echo "Share your experience with us.";}?></p>		

	</div>		

		<form id="enquiry-form" action="<?= home_url() ?>/reviews/reviews/submit" method="post">

		<?php echo  $_View->render('admin.reviews.form', compact('a')) ?>
		
		<div class="form-row form-row--submit">

			<div class="form-col submit-col">

				<button class="promo-btn mk-button dark light-color mk-smooth flat-dimension large rounded" style="background:<?php echo $formoptions['submitbutton-color']; ?>; color:<?php echo $formoptions['submitbutton-font-color']; ?>" >Submit</button>

			</div>

			<p class="small-text">By pressing Submit I acknowledge that my review may be used in online promotional material</p>

		</div> 

		</form>	

		<?php if( !is_admin() ): ?>

			

				<style> 

					.form-col.submit-col {    text-align: center;}.cr-form-wrap {    position: relative;    padding: 35px 40px;}.form-col.half-form-col { width: 48%;}.clear{	clear: both;}.half-col-play {    display: flex;    flex-wrap: wrap;    flex-direction: row;	justify-content: space-between; align-items: center;}.cr-form-wrap input, .cr-form-wrap select {    height: 45px;  width: 100%;}.cr-form-wrap .form-col {    margin-bottom: 18px;}.cr-form-wrap fieldset {    background: inherit;    margin: 0;	padding: 0;}button.promo-btn.mk-button.dark.light-color.mk-smooth.flat-dimension.large.rounded {    padding: 13px 60px;}.head-strip {    text-align: center;    margin-bottom: 20px;}.head-strip h4 {    margin: 0;    font-size: 30px;    line-height: 33px;}.head-strip p {    font-size: 16px;    margin: 0;}

					.cr-form-wrap input, .cr-form-wrap select { height: inherit !important; }

					@media only screen and (max-width: 425px) {

						.form-col.half-form-col {

								width: 100% !important;

						}

						.cr-form-wrap {

							padding: 10px !important

						}

					}

				</style>

			

			

			

			<?php endif; ?>

		</div>

