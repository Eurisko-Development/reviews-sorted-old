<?php get_header(); ?>
<?php the_post(); ?>
<!--?php echo get_schema_markup('main'); ?-->
<div id="theme-page" class="main" >
  	<div class="mk-main-wrapper-holder">
		<div class="theme-page-wrapper mk-blog-single vc_row-fluid mk-grid">
			<div class="mk-single-content" itemprop="articleBody">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); ?>