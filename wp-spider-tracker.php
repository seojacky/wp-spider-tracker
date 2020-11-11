<?php

/**
 * Plugin name: WP Spider Tracker
 * Description: Tracks search engine and other web robots/spiders activity on your WordPress blog. <a href='admin.php?page=sub-page2'>Settings</a> | <a href='admin.php?page=spider-tracker/admin.php'>Stats</a> | <a href='http://mnm-designs.com/wordpress-plugins/wp-spider-tracker/'>Support</a>
 * Version: 1.0.5
 * Author: seojacky
 * Author URI: https://t.me/big_jacky
 * Plugin URI: https://wordpress.org/plugins/wp-spider-tracker/
 * GitHub Plugin URI: https://github.com/seojacky/wp-spider-tracker
 * Text Domain: wp-spider-tracker
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


//###  SECURITY  ###//
if (!defined('ABSPATH')) die("'tsk-tsk' You should only access this page via WP-Admin!");


//###  VARIABLES  ###//
global $wpdb, $table_name, $wpstp_db_version, $wpstp_version, $wpstp_perms, $WPSTP_PATH;
$table_name = $wpdb->prefix . "wpstp_spider_tracker";
$wpstp_db_version = "1.0";
$wpstp_version = "1.0.5";
$wpstp_perms = 10;	 // http://codex.wordpress.org/User_Levels
$WPSTP_PATH = plugins_url('wp-spider-tracker');


//###  HOOKS  ###//
register_activation_hook( __FILE__, 'wpstp_install' );
add_action( 'wp_head', 'wpstp_is_bot' );
add_action( 'admin_menu', 'wpstp_admin_block' );



//###  ADMINISTRATION  ###//

	// load CSS and SCRIPT pages to the admin head 
add_action( 'admin_enqueue_scripts', function () {
wp_enqueue_style( 'wpstp-admin-style', plugin_dir_url(__FILE__) .'wpstp_styles.css' );
wp_enqueue_script( 'wpstp-admin-script', plugin_dir_url(__FILE__) .'js/wpstp_scripts.js', array('jquery') );
});




function wpstp_admin_block() {
	//Include menus
	require_once( dirname(__FILE__).'/admin.php' );
}


//###  FUNCTIONS  ###//
function wpstp_install () {
	global $wpdb, $table_name, $wpstp_db_version, $wpstp_version;	
	require_once( dirname( __FILE__ ) . '/install.php' );
}

function wpstp_is_bot() {
	global $wpdb, $table_name;
	$agent	= $_SERVER['HTTP_USER_AGENT'];
	$page	= $_SERVER['REQUEST_URI'];
	$ip		= $_SERVER['REMOTE_ADDR'];
	$host	= gethostbyaddr( $ip );
	$bots = $wpdb->get_results( "SELECT * FROM `".$table_name."` WHERE `active` = 'y'" );
	foreach($bots as $bot){
		if( stristr($agent, trim($bot->search_str)) ) {
			// Update latest user agent
			$wpdb->query( "UPDATE `".$table_name."` SET `useragent` = '".$agent."' WHERE `id` = '".$bot->id."'" );
			// Update spider stats
			$wpdb->query( "UPDATE `".$table_name."` SET `index_count` = `index_count` + 1, `last_index_time` = now() WHERE `id` = '".$bot->id."'" );
			// Log spider entry
			$wpdb->query( "INSERT INTO `".$table_name."_log` ( `id`, `ip`, `host`, `page`, `datetime` ) VALUES ( ".$bot->id.", '".$ip."', '".$host."', '".$page."', now() )" );
			// Tidy up by removing excess logs and only keeping number set by get_option("wpstp_max_rows_per_spider")
			$wpdb->query( "DELETE FROM `".$table_name."_log` WHERE `id` = ".$bot->id." AND `datetime` < (
								SELECT MIN(datetime) FROM (
									SELECT `datetime` FROM `".$table_name."_log` WHERE `id` = ".$bot->id." ORDER BY `datetime` DESC LIMIT ".get_option("wpstp_max_rows_per_spider")."
								) AS MinSelect
						   )");
			break;
		} // else discard... not a spider or one that we don't want to track as it is not found in $table_name
	}
}
?>
