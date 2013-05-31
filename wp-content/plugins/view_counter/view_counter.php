<?php
/*
Plugin Name: View Counter
Description: Adds a counter to track the number of times a post is viewed.
Author: Michael O'Connell
Version: 1.02
Author URI: http://wunder-ful.com/

	This plugin is released under version 2 of the GPL:
	http://www.opensource.org/licenses/gpl-license.php
*/
class view_counter
{
	function view_counter()
	{
		global $wpdb;
		if ( !get_settings('posts_have_view_count') )
		{
			$wpdb->query("ALTER TABLE `$wpdb->posts` ADD `view_count` BIGINT UNSIGNED NOT NULL"); 
			update_option('posts_have_view_count', 1);
		}
		
		add_filter('single_template', array(&$this, 'increment_view_count')); 
		add_filter('posts_orderby', array(&$this, 'add_orderby')); //bypass orderby safety check
	}
	
	function increment_view_count($template)
	{	
		global $wpdb, $post, $post_cache, $increment_already_ran;
		
		//workaround for *_template filters executing twice trac.wordpress.org #2225
		if($increment_already_ran)
			return $template;
		
		$p = $post->ID;
		
		if(!$p) //if the post id cannot be found cannot continue
			return $template;
		
		//check cache first
		if($post_cache[$p])
			$count = $post_cache[$p]->view_count;
		else //fallback to plain select
		{
			$result = $wpdb->get_results("SELECT `view_count` FROM `$wpdb->posts` WHERE `ID` = $p");
			$count = $result[0]->view_count;
		}
		
		$count++;
		
		//update cache
		$post_cache[$p]->view_count = $count;
		
		$wpdb->query("UPDATE `$wpdb->posts` SET `view_count` = $count WHERE `ID` = $p");
		
		$increment_already_ran = true;
		
		return $template;
	}
	
	function add_orderby($orderby)
	{
		if($_GET['orderby'] == 'view_count')
			return '`view_count` DESC';
		else
			return $orderby; 
	}
}

$view_counter =& new view_counter();

function the_view_count()
{
	echo get_view_count();
}

function get_view_count()
{
	global $post;
	
	return $post->view_count;
}
?>
