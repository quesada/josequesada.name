<?php get_header(); ?>

<?php wp_link_pages(); ?>

	<div id="hppost">
				
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	
		<div class="entry-<?php the_ID(); ?>">
						
			<h2><?php the_title(); ?></h2>
				
			</div>
	
			<div class="entry">
				<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>
	
				<?php link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>

			</div>

<div style="clear: both"></div>

<?php comments_template(); ?>
		
	<?php endwhile; else: ?>
	
		<p>Sorry, no posts matched your criteria.</p>
	
<?php endif; ?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>