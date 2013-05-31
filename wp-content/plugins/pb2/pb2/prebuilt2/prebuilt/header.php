<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">



<head profile="http://gmpg.org/xfn/11">



	<title><?php bloginfo('name'); ?><?php wp_title(); ?></title>

	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />

	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" />

	<meta name="template" content="Prebuilt Version 2" />

 	<meta name="description" content="<?php bloginfo('description'); ?>" />

 

	<link rel="stylesheet" type="text/css" media="screen" href="<?php bloginfo('stylesheet_url'); ?>" />

	<?php /*Let's get the custom CSS */ if (get_option('k2scheme') != '') { ?>

	<link rel="stylesheet" type="text/css" media="screen" href="<?php k2info('scheme'); ?>" />

	<?php } ?>



	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>" />

	<link rel="alternate" type="text/xml" title="RSS .92" href="<?php bloginfo('rss_url'); ?>" />

	<link rel="alternate" type="application/atom+xml" title="Atom 0.3" href="<?php bloginfo('atom_url'); ?>" />



<?php if (is_single() or is_page()) { ?>

	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php } ?>

	



<?php wp_get_archives('type=monthly&format=link'); ?>



<?php if (is_single() and ('open' == $post-> comment_status) or ('comment' == $post-> comment_type) ) { ?>

        <script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/prototype.js.php"></script>

        <script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/effects.js.php"></script>

        <script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/ajax_comments.js"></script>

<?php } ?>





	<?php wp_head(); ?>



</head>



<body class="<?php /* Is Flexible Width Enabled? */ if (get_option('k2widthtype') == 0) echo flex; ?> <?php if (is_single()) echo permalink; ?>">



<div id="wrap">



	<div id="header">



<ul class="menu">

			<li class="<?if (((is_home()) && !(is_paged())) or (is_archive()) or (is_single()) or (is_paged()) or (is_search())) { ?>current_page_item<?php } else { ?>page_item<?php } ?>"><a href="<?php echo get_settings('home'); ?>">Blog</a></li>

			<?php wp_list_pages('sort_column=menu_order&depth=1&title_li='); ?>

</ul>



</div>