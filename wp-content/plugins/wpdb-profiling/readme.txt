=== WPDB Profiling ===
Contributors: tierrainnovation
Donate link: http://tierra-innovation.com/wordpress-cms/2009/10/09/wpdb-profiling/
Tags: profiling,db,queries,query,db query total
Requires at least: 1.5
Tested up to: 2.9.1
Stable tag: trunk

This plugin will give you the total number of queries to the db per page, as well as the total time it took to render those queries out to the page.

== Description ==

Render database profiling at the bottom of all WordPress pages.  To install, upload `wpdb-profiling` to the `/wp-content/plugins/` directory, activate the plugin, and enable / disable features from the wp-admin plugin screen.

== Changelog ==

= 1.3.3 =

Fixing a bug where the css is always enabled, even if the plugin isn't.

= 1.3.2 =

Adds front end functionality to the wp-admin.  Helpful for debugging admin or plugin scripts under development inside wp-admin.

= 1.3 =

This version adds db optimization functionality.

1. Support to disable post revisions:  Post revisions can bloat your database by retaining previously saved versions of your post, which you may never use.
2. Support to disable auto saving:  Auto saving can bloat your database by assigning different id's each time an item is saved, this adding an unnecessary record to the db.

= 1.2.1 =

1. Update to license information

= 1.2 =

1. New configuration options to specify which user levels can view profiling (no longer restricted to Administrator)
2. ?show_queries=true can now be used in addition to ?show_queries=yes
3. Users not logged in can never view profiling

= 1.1.1 =

1. ?show_queries=yes $_GET parameter can only be used if logged in user is an administrator (user Level 10)

= 1.1 =

1. New wp-admin interface allows you to toggle plugin settings.  Options are (Always show profiling when logged in as an administrator?) and (Allow the `?show_queries=yes` parameter in URL to show profiling?)
2. Enable / Disable profiling on the front end when logged in as administrator (user Level 10) to the wp-admin interface.
3. Enable / Disable `?show_queries=yes` $_GET parameter via wp-admin config to allow / prevent url request for query list.
4. Check whether WP_CACHE and SAVEQUERIES are set on the back end - display success / failure notices and suggestions if one is missing.

= 1.0.2 =

1. Checks if `define('WP_CACHE', true);` is present.  If not, provides plugin suggestions for DB caching.
2. Plugin automatically enabled if your user has Level 10 permissions (administrator) by default.
3. If user Level < 10 or not logged in, you can continue to pass `?show_queries=yes` via the url if you are not logged in.
4. Sets `define('SAVEQUERIES', true);` in plugin should it not be by default.

The next release will have an admin interface where you can enable / disable the plugin 

= 1.0.1 =

Groups duplicate function calls and gives a total time for the grouped calls.  Also styles the profiling a bit to mirror Kohana's profiling coloring.  Also added a fix to PHP 5.2.1 where scientific notation was displaying instead of the true time.

This plugin will give you the total number of queries to the db per page, as well as the total time it took to render those queries out to the page.  Additionally, line by line each individual query will display with the originating SQL statement, time executed and the function call used to execute the query.

This is especially handy if you are debugging a slow WordPress install and can't seem to locate the cause of the load. It is also a great way to identify plugins that may not have proper caching enabled or supported.

This is NOT a full page load profiler.  This simply shows you the total DB queries and the time it takes to execute.

== Installation ==

1. Upload `wpdb-profiling.php` to the `/wp-content/plugins/wpdb-profiling/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Edit `wp-config.php` and add `define('SAVEQUERIES', true);`
1. Load any page and append `?show_queries=yes` to the end of your url to view your db queries and time to execute.

== Screenshots ==

**[View Screen Shots](http://tierra-innovation.com/wordpress-cms/2009/10/09/wpdb-profiling/)**

== Frequently Asked Questions ==

= When I view the profile, it shows no results and says Total Number of Queries: 0 and Total Time: 0 =

First, make sure in your footer.php file that you are using the `<?php wp_footer(); ?>` tag.  If you are, verify that you have added `define('SAVEQUERIES', true);` to your wp-config.php.  Need more help?  **[Support](http://tierra-innovation.com/wordpress-cms/2009/10/09/wpdb-profiling/#respond)**