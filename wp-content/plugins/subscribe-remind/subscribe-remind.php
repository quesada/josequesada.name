<?php
/*
Plugin Name: Subscribe Remind
Version: 1.3
Plugin URI: http://trevorfitzgerald.com/
Description: Give your readers a little reminder to subscribe to your blog's feed or your Twitter account at the end of each post.
Author: Trevor Fitzgerald
Author URI: http://trevorfitzgerald.com/
*/

define('TWITTER_URL', ''); // ex: http://twitter.com/fitztrev

define('SUBSCRIBE_REMIND_TEXT', 'If you enjoyed this post, make sure you <a href="%s">subscribe to my RSS feed</a>! ');
define('TWITTER_FOLLOW_TEXT', 'You can also <a href="%s">follow me on Twitter here</a>.');

function subscribe_remind($content) {
	if ( is_single() ) {
		$content .= "\n\n" . '<div><em>';
		$content .= sprintf(SUBSCRIBE_REMIND_TEXT, get_bloginfo('rss2_url'));
		if ( TWITTER_URL ) $content .= sprintf(TWITTER_FOLLOW_TEXT, TWITTER_URL);
		$content .= '</em></div>';
	}
	return $content;
}

add_filter('the_content', 'subscribe_remind');

?>
