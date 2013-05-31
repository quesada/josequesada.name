<?php
/*
Plugin Name: Install via URL
Plugin URI: http://zagalski.pl/protfolio/themes-via-url/
Description: Plugin allows wordpress users to upload themes from URL
Author: Michał Zagalski
Author URI: http://zagalski.pl
Version: 1.1
Tags: upload, install, URL, theme, via, url
License: FREE
*/


class uploadTheme {


	private $url;
	
	
	function __construct() {
		//add action
		add_action('install_themes_url', array( &$this, 'install_themes_url' ));
		add_action('install_plugins_url', array( &$this, 'install_plugins_url' ));
		add_action('update-custom_url-theme-upload', array( &$this, 'custom_url_theme_upload' ));
		add_action('update-custom_url-plugin-upload', array( &$this, 'custom_url_plugin_upload' ));
		//add filter 
		add_filter('install_themes_tabs', array( &$this, 'insert_custom_tab' ));
		add_filter('install_plugins_tabs', array( &$this, 'insert_custom_tab' ));
		
	}
	
	function insert_custom_tab($tabs) {
	//we take wordpress tabs array and add our data
		$newTab = array();
		$newTab['url'] = __( 'Via Url' );
		
			return $tabs + $newTab;
	}
	
	
	
	//show form
	function install_themes_url() {
?>
		<h4><?php _e('Install a theme in .zip format via URL') ?></h4>
		<p class="install-help"><?php _e('If you have a url to theme in a .zip format, you may fill input with URL and upload it here.') ?></p>
		<form method="post" action="<?php echo self_admin_url('update.php?action=url-theme-upload') ?>">
		<?php wp_nonce_field( 'theme-url-upload') ?>
			<input type="text" name="themeurl" style="width: 400px;" /><br><br>
			
			<?php submit_button( __( 'Install Now' ), 'button', 'install-theme-submit', false ); ?>
		</form>
<?php	
	}
	
	//show form
	function install_plugins_url() {
?>
		<h4><?php _e('Install a plugin in .zip format via URL') ?></h4>
		<p class="install-help"><?php _e('If you have a url to plugin in a .zip format, you may fill input with URL and upload it here.') ?></p>
		<form method="post" action="<?php echo self_admin_url('update.php?action=url-plugin-upload') ?>">
		<?php wp_nonce_field( 'plugin-url-upload') ?>
			<input type="text" name="pluginurl" style="width: 400px;" /><br><br>
			
			<?php submit_button( __( 'Install Now' ), 'button', 'install-plugin-submit', false ); ?>
		</form>
<?php	
	}	
	
	function custom_url_theme_upload () {
	
		//if user cannot install themes we die
		if ( ! current_user_can('install_themes') )
			wp_die(__('You do not have sufficient permissions to install themes for this site.'));

		check_admin_referer('theme-url-upload');
			
		//set variables	
		$this->url = $url = $_POST['themeurl'];
		$validExt = array('zip');
	
		//call function comparing extension
		if(self::compareExt($url, $validExt)):
			//upload file
			
			self::uploadThemeFile();
		else:
			//handle error
			self::invalidExtension();
		endif;	
	
	}
	
	function custom_url_plugin_upload () {
	
		//if user cannot install themes we die
		if ( ! current_user_can('install_plugins') )
			wp_die(__('You do not have sufficient permissions to install themes for this site.'));

		check_admin_referer('plugin-url-upload');
			
		//set variables	
		$this->url = $url = $_POST['pluginurl'];
		$validExt = array('zip');
	
		//call function comparing extension
		if(self::compareExt($url, $validExt)):
			//upload file
			
			self::uploadPlguinFile();
		else:
			//handle error
			self::invalidExtension();
		endif;	
	
	}
	
	function compareExt($url, $validExt) {
		
		//get file extension from url
		$fileExt = strtolower(pathinfo($url, PATHINFO_EXTENSION)); 
	
		//compare it and return true or false
		if(in_array($fileExt, $validExt)):
			return true;
		else:
			return false;
		endif;
	
	}
	
	function uploadThemeFile() {
	
		//set destination dir
		$destDir = ABSPATH.'wp-content/themes/';
		
		//set new file name
		$newFname = $destDir . basename($this->url);
	
		$newFile = fopen($this->url, "rb");

		if ($newFile):
		  $newf = fopen($newFname, "wb");

			if ($newf):
			  while(!feof($newFile)):
				fwrite($newf, fread($newFile, 1024 * 8 ), 1024 * 8 );
			  endwhile;
			endif;
			  
		endif;		
		
		if ($newFile):
			fclose($newFile);
			//close file handle and uncompress our archive
			self::installTheme($newFname);
		endif;
		
		if ($newf):
			fclose($newf);
		endif;
	
		
	
	}
	
	function uploadPlguinFile() {
	
		//set destination dir
		$destDir = ABSPATH.'wp-content/plugins/';
		
		//set new file name
		$newFname = $destDir . basename($this->url);
	
		$newFile = fopen($this->url, "rb");

		if ($newFile):
		  $newf = fopen($newFname, "wb");

			if ($newf):
			  while(!feof($newFile)):
				fwrite($newf, fread($newFile, 1024 * 8 ), 1024 * 8 );
			  endwhile;
			endif;
			  
		endif;		
		
		if ($newFile):
			fclose($newFile);
			//close file handle and uncompress our archive
			self::installPlugin($newFname);
		endif;
		
		if ($newf):
			fclose($newf);
		endif;
	
		
	
	}	
	
	function installTheme($file) {
	
		$title = __('Upload Theme');
		$parent_file = 'themes.php';
		$submenu_file = 'theme-install.php';
		add_thickbox();
		wp_enqueue_script('theme-preview');
		require_once(ABSPATH . 'wp-admin/admin-header.php');

		$title = sprintf( __('Installing Theme from uploaded file: %s'), basename( $file ) );
		$nonce = 'theme-upload';
		//$url = add_query_arg(array('package' => $file_upload->id), 'update.php?action=upload-theme');
		$type = 'upload';

		$upgrader = new Theme_Upgrader( new Theme_Installer_Skin( compact('type', 'title', 'nonce') ) );
		$result = $upgrader->install( $file );

		if ( $result )
			self::cleanup($file);

		include(ABSPATH . 'wp-admin/admin-footer.php');
	
	}
	
	function installPlugin($file) {

		$title = __('Upload Plugin');
		$parent_file = 'plugins.php';
		$submenu_file = 'plugin-install.php';
		require_once(ABSPATH . 'wp-admin/admin-header.php');

		$title = sprintf( __('Installing Plugin from uploaded file: %s'), basename( $file ) );
		$nonce = 'plugin-upload';
		//$url = add_query_arg(array('package' => $file_upload->id), 'update.php?action=upload-plugin');
		$type = 'upload'; //Install plugin type, From Web or an Upload.

		$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact('type', 'title', 'nonce') ) );
		$result = $upgrader->install( $file );

		if ( $result )
			self::cleanup($file);

		include(ABSPATH . 'wp-admin/admin-footer.php');
	
	}
	
	function cleanup($file) {
	
		if(file_exists($file)):
			return unlink( $file );
		endif;
	}
	
	function invalidExtension() {
	
		wp_die('Chosen archive has invalid extension');
	
	}
	
}	

new uploadTheme();


?>