<?php
/*
Plugin Name: Spider Tracker
Plugin URI: http://mnm-designs.com/wordpress-plugins/wp-spider-tracker/
Description: Tracks search engine and other web robots/spiders activity on your WordPress blog. <a href='admin.php?page=sub-page2'>Settings</a> | <a href='admin.php?page=spider-tracker/admin.php'>Stats</a> | <a href='http://mnm-designs.com/wordpress-plugins/wp-spider-tracker/'>Support</a> | <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6581225' target='_blank'>Donate</a>.
Version: 1.0.3
Author: Tony Fernandes
Author URI: http://www.mnm-designs.com
*/

/*  Copyright 2009  Tony @ MnM Designs  (email : tony@mnm-designs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


//###  SECURITY  ###//
if (!defined('ABSPATH')) die("'tsk-tsk' You should only access this page via WP-Admin!");


//###  VARIABLES  ###//
global $wpdb, $table_name, $wpstp_db_version, $wpstp_version, $wpstp_perms, $WPSTP_PATH;
$table_name = $wpdb->prefix . "mnm_spider_tracker";
$wpstp_db_version = "1.0";
$wpstp_version = "1.0.3";
$wpstp_perms = 10;	 // http://codex.wordpress.org/User_Levels
$WPSTP_PATH = WP_PLUGIN_URL."/wp-spider-tracker";


//###  HOOKS  ###//
register_activation_hook( __FILE__, 'wpstp_install' );
add_action( 'wp_head', 'wpstp_is_bot' );
add_action( 'admin_menu', 'wpstp_admin_block' );
add_action( 'admin_head', 'wpstp_admin_head' );


//###  ADMINISTRATION  ###//
function wpstp_admin_head() {
	global $WPSTP_PATH;
	// load CSS and SCRIPT pages to the admin head
    echo '
		<!-- Start of MnM Spider Tracker includes -->
		<link href="'. $WPSTP_PATH .'/wpstp_styles.css" rel="stylesheet" type="text/css" media="screen, projection" />
		<!-- End of MnM Spider Tracker includes -->

';
}

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