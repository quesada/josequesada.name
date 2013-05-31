<?php
/*
Plugin Name: Comment Relish
Plugin URI: http://www.justinshattuck.com/comment-relish/
Description: Increases your readership and RSS subscription rate by simply sending a short 'thank you' relishing type message to users when they first comment on your weblog.
Author: Justin Shattuck
Version: 1.0
Author URI: http://www.justinshattuck.com/
*/

	
/**
 * Configure the table name to use for the plugin
 *
 */
$l_TableName = $wpdb->prefix . "cr_emailed";


/**
 * Hook the admin menu action to call the cr_admin_option function
 *
 */
add_action('admin_menu', 'cr_admin_option');


/**
 * Adds the comment relish option to the administration menu
 *
 */
function cr_admin_option() {
    global $wpdb;
    if (function_exists('add_submenu_page'))
        add_submenu_page('plugins.php', __('Comment Relish'), __('Comment Relish'), 1, __FILE__, 'cr_admin_panel');
}


/**
 * Hook the activate option to create the SQL table
 *
 */
add_action('activate_comment-relish.php','cr_install');


/**
 * Run install function if not installed
 *
 */
if (!(get_option('cr_installed') == 'true')) cr_install();


/**
 * Runs installation procedures
 *
 */
function cr_install() {
	global $wpdb, $l_TableName;

	// Make sure the table hasn't been created
	if($wpdb->get_var("SHOW tables LIKE '$l_TableName'") != $l_TableName) {
		update_option('cr_installed','false');

		// Build the query
		$sql = "CREATE TABLE ".$l_TableName." (
						emailed_ID mediumint(9) NOT NULL AUTO_INCREMENT,
						time bigint(11) DEFAULT '0' NOT NULL,
						email tinytext NOT NULL,
						UNIQUE KEY emailed_ID (emailed_ID)
					 );";

		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);

		// Find all current emails
		$sql = "SELECT c.comment_author_email FROM $wpdb->comments c
				   LEFT JOIN $l_TableName e ON e.email = c.comment_author_email
				   WHERE e.email IS NULL
				   GROUP BY c.comment_author_email";

		// Execute
		$result = $wpdb->get_results($sql);

		// Loop through the results
		foreach ($result as $email) {

			$email = $email->comment_author_email;

			// Add to the emailed list
			$wpdb->query("INSERT INTO $l_TableName (time, email) VALUES ('" . time() . "', '" . __($email) . "')");
		}

		update_option('cr_installed','true');
	}
}


/**
 * Builds the administration panel to configure the plugin
 *
 */
function cr_admin_panel() {
	// Update the options
    if ($_POST['stage'] == 'process') {
        update_option('cr_enabled',$_POST['cr_enabled']);
        update_option('cr_relish_from_name',$_POST['cr_relish_from_name']);
        update_option('cr_relish_from_email',$_POST['cr_relish_from_email']);
        update_option('cr_relish_subject',$_POST['cr_relish_subject']);
        update_option('cr_relish_message',$_POST['cr_relish_message']);
		?> <div class="updated"><p>Preferences saved!</p></div> <?php
    }
    ?>
    <div class="wrap">
        <h2 id="write-post">Comment Relish Preferences</h2>
        <p><strong>Intended Purpose:</strong>  Send a relishing, <em>thank-you</em> e-mail message to new commentors on your weblog.  When a user comments on your blog, if they have never commented before, they will be sent the message specified below.</p>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=comment-relish.php">
            <input type="hidden" name="stage" value="process" />
            <fieldset class="options">
                <table>
                    <tr>
                        <td>Enable Comment Relish</td>
                        <td>
							<select name="cr_enabled" id="cr_enabled">
								<option <?php if (get_option("cr_enabled") == "true") echo "selected"; ?> value="true">True</option>
								<option <?php if (get_option("cr_enabled") != "true") echo "selected"; ?> value="false">False</option>
                            </select>
						</td>
                    </tr>
                    <tr>
                        <td>Relish From Name</td>
                        <td>
							<input type="text" name="cr_relish_from_name" id="cr_relish_from_name" value="<?=get_option("cr_relish_from_name");?>" />
						</td>
                    </tr>
                    <tr>
                        <td>Relish From Email</td>
                        <td>
							<input type="text" name="cr_relish_from_email" id="cr_relish_from_email" value="<?=get_option("cr_relish_from_email");?>" />
						</td>
                    </tr>
                    <tr>
                        <td>Relish Subject</td>
                        <td>
							<input type="text" name="cr_relish_subject" id="cr_relish_subject" value="<?=get_option("cr_relish_subject");?>" />
						</td>
                    </tr>
                    <tr>
                        <td>Relish Message</td>
                        <td>
							<textarea name="cr_relish_message" id="cr_relish_message" rows="7" cols="70"><?=get_option("cr_relish_message");?></textarea>
						</td>
                    </tr>
                </table>
	<h2 id="write-post">Available Tags</h2>
	<p>The following list of tags are embeddable within the subject line and content body of the e-mail message you create.  Simply insert the tag (IE: %AUTHOR%) where you would like the Author's name to appear.</p>
				<div class="cr_keys_box">	
					<div id="cr_key_author">%AUTHOR% - Author's Name</div>
					<div id="cr_key_author_url">%AUTHOR_URL% - Author's Website</div>
					<div id="cr_key_author_email">%AUTHOR_EMAIL% - Author's Email</div>
					<div id="cr_key_comment">%COMMENT% - Comment Posted</div>
					<div id="cr_key_article">%ARTICLE% - URL to Article</div>
					<div id="cr_key_comment_id">%COMMENT_ID% - Comment's ID</div>
					<div id="cr_key_feed_rss20">%FEED_RSS2.0% - Website's RSS 2.0 Feed URL</div>
					<div id="cr_key_date_short">%DATE_SHORT% - Short date (eg: 5/25/2006)</div>
					<div id="cr_key_date_long">%DATE_LONG% - Long date (eg: May 25th, 2006)</div>
					<div id="cr_key_datetime_short">%DATETIME_SHORT% - Short datetime (eg: 5/25/2006 10:58:35AM)</div>
					<div id="cr_key_datetime_long">%DATETIME_LONG% - Long datetime (eg: May 25th, 2006 10:58:35AM)</div>
				</div>
            </fieldset>
            <p class="submit"><input type="submit" value="Update Preferences &raquo;" name="Submit" /></p>
        </form>
    </div>
    <?            
}


/**
 * Hook the DEactivate option to disable the plugin
 *
 */
add_action('deactivate_comment-relish.php','cr_uninstall');


/**
 * Runs uninstallation
 *
 */
function cr_uninstall() {
	// Disable the plugin to prevent instant emailing on reactivation
	update_option('cr_enabled','false');

	// Mark the plugin not installed
	update_option('cr_installed','false');
}


// Only run if enabled
if (get_option("cr_enabled") == "true") {
	/**
	 * Because some of our functions are dependent on later loaded modules, hook init and run later
	 *
	 */
	add_action('init', 'cr_send_emails');


	/**
	 * Find and email new commentors
	 *
	 */
	function cr_send_emails() {
		global $wpdb, $l_TableName;

		// Find new commentors
		$l_NewAuthor = $wpdb->get_results("SELECT c.*, p.*
															   FROM $wpdb->comments c
															   INNER JOIN $wpdb->posts p ON p.ID = c.comment_post_ID
															   LEFT JOIN $l_TableName e ON e.email = c.comment_author_email
															   WHERE e.email IS NULL AND c.comment_approved = '1'");

		// Fetch the relish options
		$l_RelishSubject = get_option("cr_relish_subject");
		$l_RelishMessage = get_option("cr_relish_message");
		$l_RelishFromName = get_option("cr_relish_from_name");
		$l_RelishFromEmail = get_option("cr_relish_from_email");
		$l_Headers = "MIME-Version: 1.0\n" .
		  "From: " . __($l_RelishFromName) . " <" . $l_RelishFromEmail . ">\n";

		// Loop through each one
		$l_Processed = array();
		foreach ($l_NewAuthor as $l_Author) {
			// Make sure we haven't processed this email
			if (!in_array($l_Author->comment_author_email, $l_Processed)) {
				// Build sql query to create a new row in the emailed table
				$l_SQL = "INSERT INTO $l_TableName (time, email) VALUES ('". time() . "', '" . __($l_Author->comment_author_email) . "');";

				// Execute the query
				$wpdb->query($l_SQL);
				$l_EmailedID =  $wpdb->insert_id;

				// Attempt to send the email
				if (FALSE == mail($l_Author->comment_author_email, cr_format_email($l_RelishSubject, $l_Author), cr_format_email($l_RelishMessage, $l_Author), $l_Headers)) {
					// Delete the emailed row
					$wpdb->query("DELETE FROM $l_TableName WHERE emailed_ID='" . (int)$l_EmailedID . "'");

					// Error out
					die('<p>' . __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...') . '</p>');
				}

				// Add the email to the processed array
				$l_Processed[] = $l_Author->comment_author_email;
			}
		}
	}


	/**
	 * Swaps the keys with the values in the email message
	 *
	 * @param	 string	$p_Message	Email message
	 * @param	 array	$p_Author	Comment and post information per author
	 */
	function cr_format_email($p_Message, $p_Author) {
		// Figure out the feed URL
		if ( '' != get_option('permalink_structure') )
			$url = get_option('home') . '/feed/';
		else
			$url = get_option('home') . "/$commentsrssfilename?feed=rss2";

		// Build a list of values to change
		$l_SwapValues = array("%AUTHOR%"				=> $p_Author->comment_author,
										   "%AUTHOR_URL%"		=> $p_Author->comment_author_url,
										   "%AUTHOR_EMAIL%"	=> $p_Author->comment_author_email,
										   "%COMMENT%"			=> $p_Author->comment_content,
										   "%ARTICLE%"			=> get_permalink($p_Author->comment_post_ID),
										   "%COMMENT_ID%"		=> $p_Author->comment_ID,
										   "%FEED_RSS2.0%"		=> $url,
										   "%DATE_SHORT%"	=> date("n/j/Y"),
										   "%DATE_LONG%"	=> date("F jS, Y"),
										   "%DATETIME_SHORT%"	=> date("n/j/Y h:i:sA"),
										   "%DATETIME_LONG%"	=> date("F jS, Y h:i:sA"));

		// Swap out old with new
		$p_Message = str_replace(array_keys($l_SwapValues), array_values($l_SwapValues), $p_Message);

		return $p_Message;
	}
}

?>
