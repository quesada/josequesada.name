<?php
/*
Plugin Name: WPDB Profiling
Plugin URI: http://www.tierra-innovation.com/blog/2009/07/01/wpdb-profiling-1-1-released/
Description: Render database profiling below the wp_footer() variable.  Upload `wpdb-profiling` to the `/wp-content/plugins/` directory, activate the plugin, and enable / disable features from the wp-admin plugin screen.
Author: Tierra Innovation
Version: 1.3.3
Author URI: http://www.tierra-innovation.com
*/

/*
 * This is a modified version (under the MIT License) of a plugin
 * originally developed by Tierra Innovation for WNET.org.
 * 
 * This plugin is currently available for use in all personal
 * or commercial projects under both MIT and GPL licenses. This
 * means that you can choose the license that best suits your
 * project, and use it accordingly.
 *
 * MIT License: http://www.tierra-innovation.com/license/MIT-LICENSE.txt
 * GPL2 License: http://www.tierra-innovation.com/license/GPL-LICENSE.txt
 */

add_action('wp_head', 'wpdb_profiling_head',1);
add_action('admin_head', 'wpdb_profiling_head',1);
add_action('wp_footer', 'wpdb_profiling', 1000);
add_action('admin_footer', 'wpdb_profiling', 1000);


if ( !defined('SAVEQUERIES') )
	define('SAVEQUERIES', true);

function wpdb_profiling_head() {
	if(@file_exists(TEMPLATEPATH.'/wpdb-profiling.css')) {
		echo '<link rel="stylesheet" href="'.get_stylesheet_directory_uri().'/wpdb-profiling.css" type="text/css" />'."\n";
	} else {
		echo '<link rel="stylesheet" type="text/css" href="' . get_settings('siteurl') . '/wp-content/plugins/wpdb-profiling/wpdb-profiling.css" />'."\n";
	}
}

function wpdb_profiling() {

	global $wpdb;

	$permission_1 = explode(",", get_option('profiling_user_permission_1'));
	$permission_2 = explode(",", get_option('profiling_user_permission_2'));

	$display_1 = false;
	foreach ($permission_1 as $permission)
	{
		if (current_user_can($permission))
			$display_1 = true;

		if ($display_1 == true)
			break;
	}

	$display_2 = false;
	foreach ($permission_2 as $permission)
	{
		if (current_user_can($permission))
			$display_2 = true;

		if ($display_2 == true)
			break;
	}

	if (
			get_option('always_show_profiling') == "yes"
			&& $display_1 == true
			|| 
			($_GET['show_queries'] == "yes" || $_GET['show_queries'] == "true")
			&& get_option('allow_get_request') == "yes" 
			&& $display_2 == true
		) {

		$rows = array();
		$total_time = 0;
		$total_time_by_function = array();

		foreach ($wpdb->queries as $query) {
			if ( !isset($total_time_by_function[$query[2]]) ) {
				$total_time_by_function[$query[2]] = 0;
			}
			$total_time_by_function[$query[2]] += $query[1];
			$total_time += $query[1];
			$query_time = number_format($query[1], 7, '.', '');
			$rows[] = array($query[1], "
				<tr>
					<td>$query[0]</td>\n
					<td>$query_time</td>\n
					<td>$query[2]</td>
				</tr>\n
			");
		}

		usort($rows, "wpdb_sort_output");
		arsort($total_time_by_function);

		echo "<div id='wpdb-profiling'>\n";

		if ( defined('WP_CACHE') ) {
			$style = "green";
			$cached = "<strong>Enabled!</strong>";
		}
		else {
			$style = "red";
			$cached = "<strong>Disabled!</strong>  Install a caching tool (<a href='http://wordpress.org/extend/plugins/batcache/' title='Batcache' target='_blank'>Batcache</a>) or (<a href='http://wordpress.org/extend/plugins/wp-super-cache/' title='WP_Super_Cache' target='_blank'>WP_Super_Cache</a>) for DB Optimization.";
		}

		echo "<table border='2'>\n";
		echo "<tr>\n";
		echo "
			<th width='50%'>Item</th>\n
			<th width='50%'>Status</th>\n
		";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "
			<td><strong>WP_Cache Status:</strong></td>\n
			<td class='$style'>$cached</td>\n
		";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='2'>\n";
		echo "<tr>\n";
		echo "
			<th>SQL</th>\n
			<th>Execution Time</th>\n
			<th>Function Call</th>\n
		";
		echo "</tr>\n";

		foreach ($rows as $row)
			echo $row[1];

		echo "<tr>\n";
		echo "	<td colspan='3'><strong>Total Time:</strong> " . count($wpdb->queries) . " database queries run in " . $total_time . " seconds.</td>";
		echo "</tr>\n";

		echo "</table>\n";

		if ( !empty($total_time_by_function) ) {

		echo "<table border='2'>\n";
		echo "<tr>\n";
		echo "
			<th>Grouped Functions</th>\n
			<th>Execution Time</th>\n
		";
		echo "</tr>\n";

		foreach ($total_time_by_function as $function => $time) {
			echo "<tr>\n";
			echo "<td>$function</td>";
			echo "<td>" . number_format($time, 7, '.', '') . "</td>";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		echo "
			<td><strong>Total Time</strong></td>\n
			<td><strong>" . array_sum($total_time_by_function) . "</td></strong>
		";
		echo "</tr>\n";

		echo "</table>\n";

		echo "</div>\n";

		}
	}
}

function wpdb_sort_output($a, $b) {
	return $a[0] == $b[0] ? 0 : (($a[0] > $b[0]) ? -1 : 1);
}

function set_wpdb_profiling_options() {
	$always_show_profiling		= get_option('always_show_profiling');
	$allow_get_request		= get_option('allow_get_request');

	$profiling_user_permission_1	= get_option('profiling_user_permission_1');
	$profiling_user_permission_2	= get_option('profiling_user_permission_2');

	$override_disable_auto_save	= get_option('override_disable_auto_save');
	$override_disable_revisioning	= get_option('override_disable_revisioning');

	if ($always_show_profiling !== "no")
		add_option('always_show_profiling','yes');

	if ($allow_get_request !== "yes")
		add_option('allow_get_request','no');

	if ($profiling_user_permission_1 == '')
		add_option('profiling_user_permission_1','8,9,10');

	if ($profiling_user_permission_2 == '')
		add_option('profiling_user_permission_2','8,9,10');

	if ($override_disable_auto_save !== "no")
		add_option('override_disable_auto_save','yes');

	if ($override_disable_revisioning !== "yes")
		add_option('override_disable_revisioning','no');

}

function unset_wpdb_profiling_options() {
	delete_option('always_show_profiling');
	delete_option('allow_get_request');
	delete_option('profiling_user_permission_1');
	delete_option('profiling_user_permission_2');
	delete_option('override_disable_auto_save');
	delete_option('override_disable_revisioning');
}

function admin_profiling_options() {

	if ($_REQUEST['submit'])
		update_profiling_options();

	print_profiling_form();
}

function update_profiling_options() {

	$ok = false;

	if ($_REQUEST['always_show_profiling'])
	{
		update_option('always_show_profiling',$_REQUEST['always_show_profiling']);
		$ok = true;
	}

	if ($_REQUEST['allow_get_request'])
	{
		update_option('allow_get_request',$_REQUEST['allow_get_request']);
		$ok = true;
	}

	if ($_REQUEST['profiling_user_permission_1'])
	{
		update_option('profiling_user_permission_1',$_REQUEST['profiling_user_permission_1']);
		$ok = true;
	}

	if ($_REQUEST['profiling_user_permission_2'])
	{
		update_option('profiling_user_permission_2',$_REQUEST['profiling_user_permission_2']);
		$ok = true;
	}

	if ($_REQUEST['override_disable_auto_save'])
	{
		update_option('override_disable_auto_save',$_REQUEST['override_disable_auto_save']);
		$ok = true;
	}

	if ($_REQUEST['override_disable_revisioning'])
	{
		update_option('override_disable_revisioning',$_REQUEST['override_disable_revisioning']);
		$ok = true;
	}

	if ($ok)
	{
		echo '<div id="message" class="updated fade">
			<p>Options saved.</p>
		</div>';
	}
	else
	{
		echo '<div id="message" class="error fade">
			<p>Failed to save options.</p>
		</div>';
	}

}

function print_profiling_form() {

	$true_selected = '';
	$false_selected = '';

	if (get_option('always_show_profiling') == "yes")
		$true_selected = ' selected="selected"';
	else
		$false_selected = ' selected="selected"';

	if (get_option('allow_get_request') == "yes")
		$get_true_selected = ' selected="selected"';
	else
		$get_false_selected = ' selected="selected"';

	if (get_option('profiling_user_permission_1') == "1")
		$perm_1_1_selected = ' selected="selected"';
	elseif (get_option('profiling_user_permission_1') == "2,3,4")
		$perm_1_2_selected = ' selected="selected"';
	elseif (get_option('profiling_user_permission_1') == "5,6,7")
		$perm_1_3_selected = ' selected="selected"';
	elseif (get_option('profiling_user_permission_1') == "8,9,10")
		$perm_1_4_selected = ' selected="selected"';

	if (get_option('profiling_user_permission_2') == "1")
		$perm_2_1_selected = ' selected="selected"';
	elseif (get_option('profiling_user_permission_2') == "2,3,4")
		$perm_2_2_selected = ' selected="selected"';
	elseif (get_option('profiling_user_permission_2') == "5,6,7")
		$perm_2_3_selected = ' selected="selected"';
	elseif (get_option('profiling_user_permission_2') == "8,9,10")
		$perm_2_4_selected = ' selected="selected"';

	if (get_option('override_disable_auto_save') == "yes")
		$true_autosave_selected = ' selected="selected"';
	else
		$false_autosave_selected = ' selected="selected"';

	if (get_option('override_disable_revisioning') == "yes")
		$true_revision_selected = ' selected="selected"';
	else
		$false_revision_selected = ' selected="selected"';

	print '

	<div class="wrap">

		<div id="icon-options-general" class="icon32"><img src="http://tierra-innovation.com/wordpress-cms/logos/src/wpdb-profiling/1.3.2/default.gif" alt="" title="" /><br /></div>

		<h2>WPDB Profiling &amp; Optimization</h2>

	';

	if ( defined('WP_CACHE') ) {
		$style = "green";
		$cached = "<strong>Good!</strong> WP_CACHE is defined.";
	}
	else {
		$style = "red";
		$cached = "<strong>Uh Oh!</strong>  You'll need to enable and install a caching tool (<a href='http://wordpress.org/extend/plugins/batcache/' title='Batcache' target='_blank'>Batcache</a>) or (<a href='http://wordpress.org/extend/plugins/wp-super-cache/' title='WP_Super_Cache' target='_blank'>WP_Super_Cache</a>) for DB Optimization.";
	}

	if ( defined('SAVEQUERIES') ) {
		$stylesaved = "green";
		$saved = "<strong>Good!</strong> SAVEQUERIES is defined in wp-config.php";
	}
	else {
		$stylesaved = "red";
		$saved = "<strong>Uh Oh!</strong>  Please add `define('SAVEQUERIES', true);` to your wp-config.php file.";
	}

	print "

		<h3>Plugin Check</h3>

		<ul>
			<li><span style='color: $style'>$cached</span><br />
			<span style='color: $stylesaved'>$saved</span></li>
		</ul>
	
	";

	print "

			<style type='text/css'>
				select.wpdb { width: 120px; }
			</style>

			<form method='post'>

				<h3>Database Profiling</h3>

				<ul>
					<li>
						<select class='wpdb' name='always_show_profiling'>
							<option value='yes' $true_selected>Always</option>
							<option value='no' $false_selected>Never</option>
						</select>

						<label for='always_show_profiling'>automatically show profiling when logged in as </label>

						<select class='wpdb' name='profiling_user_permission_1'>
							<option value='8,9,10' $perm_1_4_selected>Administrator</option>
							<option value='5,6,7' $perm_1_3_selected>Editor</option>
							<option value='2,3,4' $perm_1_2_selected>Author</option>
							<option value='1' $perm_1_1_selected>Contributor</option>
						</select>
					</li>

					<li>
						<select class='wpdb' name='allow_get_request'>
							<option value='yes' $get_true_selected>Enable</option>
							<option value='no' $get_false_selected>Disabled</option>
						</select>

						<label for='allow_get_request'>the `?show_queries=yes` parameter in URL for user level</label>

						<select class='wpdb' name='profiling_user_permission_2'>
							<option value='8,9,10' $perm_2_4_selected>Administrator</option>
							<option value='5,6,7' $perm_2_3_selected>Editor</option>
							<option value='2,3,4' $perm_2_2_selected>Author</option>
							<option value='1' $perm_2_1_selected>Contributor</option>
						</select>
					</li>
				</ul>

				<p><strong>Note:</strong> Select the lowest user permission level that should have access to profiling. The level you select and all higher levels will have access.</p>

				<h3>Database Optimization</h3>

				<ul>
					<li>
						<select class='wpdb' name='override_disable_auto_save'>
							<option value='yes' $true_autosave_selected>Yes</option>
							<option value='no' $false_autosave_selected>No</option>
						</select>

						<label for='override_disable_auto_save'>Disable Auto Saving</label>
					</li>
					<li>
						<select class='wpdb' name='override_disable_revisioning'>
							<option value='yes' $true_revision_selected>Yes</option>
							<option value='no' $false_revision_selected>No</option>
						</select>

						<label for='override_disable_revisioning'>Disable Post Revisions</label>
					</li>
				</ul>

				<p><input type='submit' name='submit' class='button-primary' value='Save Options' /></p>

			</form>

		</div>

	";

}

if (get_option('always_show_profiling') == "yes") {

	add_action('wp_head', 'wpdb_profiling_head',1);
	add_action('admin_head', 'wpdb_profiling_head',1);
	add_action('wp_footer', 'wpdb_profiling', 1000);
	add_action('admin_footer', 'wpdb_profiling', 1000);

}

if ( get_option('override_disable_auto_save') == "yes" ) {

	function wpdb_disable_autosave() {
		wp_deregister_script('autosave');
	}

	add_action( 'wp_print_scripts', 'wpdb_disable_autosave' );

}

if ( get_option('override_disable_revisioning') == "yes" ) {

	define('WP_POST_REVISIONS', false);

}

function modify_menu() {
	add_options_page(
		'WPDB Profiling', // page title
		'WPDB Profiling', // sub-menu title
		'manage_options', // access/capa
		'wpdb-profiling.php', // file
		'admin_profiling_options' // function
	);
}

add_action('admin_menu', 'modify_menu');

register_activation_hook(__FILE__,'set_wpdb_profiling_options');
register_deactivation_hook(__FILE__,'unset_wpdb_profiling_options');
?>