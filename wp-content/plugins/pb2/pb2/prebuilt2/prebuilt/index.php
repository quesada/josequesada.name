<?php get_header(); ?>
<?php get_sidebar(); ?>

<div id="hppost">
  <?php if (have_posts()) : ?>
  <?php while (have_posts()) : the_post(); ?>
  <div class="post" id="post-<?php the_ID(); ?>">
    <h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>">
      <?php the_title(); ?>
      </a></h2>
    <div class="entry">
      <?php if (is_archive() or is_search()) { 

						the_excerpt();

					} else {

						the_content("Read on '" . the_title('', '', false) . "'");

					} ?>
      <?php link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>
    </div>
  </div>
  <?php endwhile; ?>
  <div class="navigation">
    <div class="alignleft">
      <?php next_posts_link('&laquo; Previous Entries') ?>
    </div>
    <div class="alignright">
      <?php previous_posts_link('Next Entries &raquo;') ?>
    </div>
  </div>
  <?php else : ?>
  <h2 class="center">Not Found</h2>
  <p class="center">Sorry, but you are looking for something that isn't here.</p>
  <?php endif; ?>
</div>
<?php get_footer(); ?>
