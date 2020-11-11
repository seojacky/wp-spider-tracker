<?php
/*  Copyright 2020  big_jacky  (telegram : https://t.me/big_jacky)

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


global $wpstp_perms;

// Add a new top-level menu:
add_menu_page( 'Spider Tracker', 'Spider Tracker', $wpstp_perms, __FILE__, 'wpstp_sublevel_stats' );

// Add a submenu to the custom top-level menu:
add_submenu_page( __FILE__, 'Spider Tracker - Active Spiders Stats', 'Stats', $wpstp_perms, __FILE__, 'wpstp_sublevel_stats' );

// Add a second submenu to the custom top-level menu:
add_submenu_page( __FILE__, 'Spider Tracker - Settings', 'Settings', $wpstp_perms, 'sub-page2', 'wpstp_sublevel_settings' );




// wpstp_sublevel_stats() displays the page content for the first submenu Stats
function wpstp_sublevel_stats() {
	global $wpdb, $table_name, $WPSTP_PATH;
	$slideDelay = get_option( "wpstp_slide_delay" );
	// jQuery

	echo '	<script type="text/javascript">
				jQuery(document).ready(function($) {		
					//toggle single
					$(".wpstp_header").click(function(){
						$(this).next(".wpstp_subTable").slideToggle('. $slideDelay .')
						return false;
					});			
					//collapse all
					$(".collapse_all").click(function(){
						$(".wpstp_subTable").slideUp('. $slideDelay .')
						return false;
					});		
					//expand all
					$(".expand_all").click(function(){
						$(".wpstp_subTable").slideDown('. $slideDelay .')
						return false;
					});		
				});	
			</script>';
	
	// Set preference expanded/collapsed state
	// Default is c='collapse' but if 'c' is not set it will default to 'expand' ('e')
	if( get_option( 'wpstp_log_state' ) == 'c' ) {
		echo '	<script type="text/javascript">
					jQuery(document).ready(function($) {
						$(".wpstp_subTable").slideUp(1000)
						return false;
					});
				</script>
				';			
	}
	
	// Stats page
	echo "<div class='wrap'>
			<h2>Spider Tracker - Active Spiders</h2>";
			
	echo 	'<font style="font-size: 10pt">{ <a href="#" class="collapse_all">Collapse all</a> - <a href="#" class="expand_all">Expand all</a> }</font>
			<table class="widefat" cellspacing="0" id="">
				<tr>
					<th width="20">&nbsp;</th>
					<th width="20%" nowrap="nowrap">Name</th>
					<th width="20%" nowrap="nowrap">Last Index</th>
					<th width="*" nowrap="nowrap">Index Count</th>
					<th width="350" nowrap="nowrap">Percentage</th>
				</tr>
			</table>';
			
	$st_sum	= $wpdb->get_var( "SELECT SUM(index_count) FROM `".$table_name."` WHERE `active`='y'" );
	$st_max	= $wpdb->get_var( "SELECT MAX(index_count) FROM `".$table_name."` WHERE `active`='y'" );
	if( $st_sum == $st_max ) $st_scale = 350; else $st_scale = 280;
	
	$rows	= $wpdb->get_results( "SELECT * FROM `".$table_name."` WHERE `active`='y' ORDER BY `index_count` DESC" );				
	foreach( $rows as $row ) {
	
		if( $st_sum > 0 ) {
			$st_percent = $row->index_count / $st_sum * 100;
			$bar_width = $row->index_count / $st_max * $st_scale;
		}else {
			$st_percent = 0;
			$bar_width = 0;
		}
		
		$st_percent = number_format( $st_percent, 2 );
		$bar_width = number_format( $bar_width, 2 );		
		echo '	<div class="wpstp_header">
				<table class="widefat" cellspacing="0" id="">
					<thead>
					<tr>
						<th width="20"><img src="'.$WPSTP_PATH.'/images/icon_info.png" alt="'.$row->useragent.'" border=0></th>
						<th width="20%"><a href="'.$row->url.'" target="_blank" title="Right-click &raquo; Open to follow Spider link" alt="'.$row->name.'">'.$row->name.'</a></th>
						<th width="20%">'.$row->last_index_time.'</th>
						<th width="*"><span title="'.$row->index_count.' hits since '.$row->set_from.'">'.$row->index_count.'</span></th>
						<th width="350">							
						<div class="heat_bar" style="width:100%;">
							<span class="end_left"></span>
							<span class="end_right"></span>
							<span class="color '.get_option('wpstp_pct_bar_color').'" title="'.$st_percent.'%" style="width:'.$bar_width.'px"></span>
							<span class="text_left"></span>
							<span class="text_right">'.$st_percent.'%</span>
						</div>							
						</th>
					</tr>
					</thead>
				</table>
				</div>';
				
				
		echo '		<div class="wpstp_subTable">
					<table class="widefat" cellspacing="0" id="">
						<tr>
							<th>Time Indexed</th>
							<th>IP</th>
							<th>Hostname</th>
							<th>Page</th>
						</tr>';
					$logs = $wpdb->get_results( "SELECT * FROM `".$table_name."_log` WHERE `id` = ".$row->id." ORDER BY `datetime` DESC LIMIT ".get_option("wpstp_max_rows_per_spider")."" );
					foreach( $logs as $log ) {
		echo '			<tr>
							<td width=150>'.$log->datetime.'</td>
							<td width=100>'.$log->ip.'</td>
							<td>'.$log->host.'</td>
							<td><a href="'.$log->page.'" target="_blank">'.$log->page.'</a></td>
						</tr>';
					}
		echo '		</table>
					</div>';
	}
	echo "</div>";
	echo '<br />
			<div style="text-align: center;">
				<a href="admin.php?page=sub-page2">Settings</a>&nbsp;|&nbsp;<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6581225" target="_blank">Donate</a>
			</div><p>&nbsp;</p>';
}

// wpstp_sublevel_settings() displays the page content for the second submenu Settings
function wpstp_sublevel_settings() {
	global $wpdb, $table_name, $WPSTP_PATH;
	echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br /></div><h2>Spider Tracker - Settings</h2>';
	
	// Deal with REQUESTs
	if ( count( $_REQUEST ) > 0 ) { 
		if ( sanitize_text_field($_POST['wpstp_submitted_setts']) == "yes" ) {			// update settings
			$wpstp_slide_delay = sanitize_text_field($_POST['wpstp_slide_delay']);
			if ( is_numeric( $wpstp_slide_delay ) && !empty( $wpstp_slide_delay ) )
				update_option( "wpstp_slide_delay", $wpstp_slide_delay );
			else $msg = "Warning: Expected numeric value for '<b>Slide effect delay</b>'";				
			
			$wpstp_max_rows_per_spider = sanitize_text_field($_POST['wpstp_max_rows_per_spider']);
			if ( is_numeric( $wpstp_max_rows_per_spider ) && !empty( $wpstp_max_rows_per_spider ) )
				update_option( "wpstp_max_rows_per_spider", $wpstp_max_rows_per_spider );
			else $msg = "Warning: Expected numeric value for '<b>Max pages per spider</b>'";			
			
			if ( $msg == "" ) {
				update_option( "wpstp_log_state", sanitize_text_field($_POST['wpstp_log_state']) );
				update_option( "wpstp_pct_bar_color", sanitize_text_field($_POST['wpstp_pct_bar_color']) );
				$msg = 'Settings saved.';
			}
		}elseif ( sanitize_text_field($_POST['wpstp_submitted_new']) == "yes" ) {		// add new spider
			$name 		= stripslashes(sanitize_text_field($_POST['name']) );
			$search_str	= stripslashes(sanitize_text_field($_POST['search_str']) );
			$url		= stripslashes(sanitize_text_field($_POST['url']) );
			if( empty( $name ) || empty( $search_str ) ) {
				$msg_new = 'Warning: Both \'<b>Name</b>\' and \'<b>Search String</b>\' are required - please fill them in and retry.';
			}else {
				$bot = $wpdb->get_row( "SELECT `name`, `search_str` FROM `".$table_name."` WHERE `name` = '".$name."' OR `search_str` = '".$search_str."'" );
				if( $bot->name != "" || $bot->search_str != "" ) {
					$msg_new = 'Warning: A spider with the same name or search string already exists.</p><p>New spider <b>NOT</b> added.';
				}else {
					$bot = $wpdb->query( "INSERT INTO `".$table_name."` ( `name`, `search_str`, `url`, `set_from` )
																 VALUES ( '".$name."', '".$search_str."', '".$url."', now() )" );
					if( $bot ) $msg_new = 'New spider added!'; else $msg_new = "Error: Could not insert new spider details.";
				}
			}
		} elseif ( null !== sanitize_text_field($_GET['active'] ) )  {						// activate or deactivate spider
			$active	= sanitize_text_field($_GET['active']);
			$id		= sanitize_text_field($_GET['id']);
			if( ($active == "y" || $active == "n") && is_numeric($id) ) {
				$wpdb->query( "UPDATE `".$table_name."` SET `active` = '".$active."' WHERE `id` = ".$id );
			}
		} elseif ( sanitize_text_field($_POST['wpstp_submitted_edit']) == "yes" ) {		// edit spider
			$id 		= sanitize_text_field($_POST['wpstp_id']);
			$name 		= stripslashes(sanitize_text_field($_POST['name']) );
			$search_str	= stripslashes(sanitize_text_field($_POST['search_str']) );
			$url		= stripslashes(sanitize_text_field($_POST['url']) );
			if( empty( $name ) || empty( $search_str ) ) {
				$msg_list = 'Warning: Both \'<b>Name</b>\' and \'<b>Search String</b>\' are required - please fill them in and retry.';
			}else {
				$bot = $wpdb->query( "UPDATE `".$table_name."` SET `name` = '".$name."', `search_str` = '".$search_str."', `url` = '".$url."' WHERE `id` = ". $id );
				if( $bot ) $msg_list = $name . ' successfully edited!'; else $msg_list = "Error: Could not save changes.";
			}
		}elseif ( is_numeric( sanitize_text_field($_GET['reset'] ) ) )  {					// reset spider stats
			$id = sanitize_text_field($_GET['reset']);
			$name = $wpdb->get_var( "SELECT `name` FROM `".$table_name."` WHERE `id` = ". $id );
			$reset = $wpdb->query( "UPDATE `".$table_name."` SET `index_count` = 0 WHERE `id` = ".$id );
			if( $reset ) {
				$reset_log = $wpdb->query( "DELETE FROM `".$table_name."_log` WHERE `id` = ".$id );
				if( $reset || $reset_log ) $msg_list = 'The stats for '.$name.' have all been reset.';
			}else {
				$msg_list = 'Error: Could not reset stats for '.$name;
			}
		}elseif ( is_numeric( sanitize_text_field($_GET['delete'] ) ) ) {					// delete spider
			$id = sanitize_text_field($_GET['delete']);
			$del = $wpdb->query( "DELETE FROM `".$table_name."` WHERE `id` = ".$id );
			if( $del ) {
				$del_log = $wpdb->query( "DELETE FROM `".$table_name."_log` WHERE `id` = ".$id );
				if( $del || $del_log ) $msg_list = 'The spider and its index entries have all been deleted.';
			}else {
				$msg_list = 'Error: Could not delete the spider';
			}
		}
	}		
	if( $msg ) echo '<div id="message" class="updated fade"><p>'.$msg.'</p></div>';
	
	
	// Display the page
	if( sanitize_text_field($_GET['show'])=='edit' && is_numeric(sanitize_text_field($_GET['id'] ) ) ) {
					
			echo '<a href="javascript:history.go(-1)">&laquo; back</a>';
			$id = sanitize_text_field($_GET['id']);
			$bot = $wpdb->get_row( "SELECT * FROM `".$table_name."` WHERE `id` = ". $id );
			
			?>				
			<a name="edit"></a><h2>Editing '<?php echo $bot->name; ?>'</h2>
			<form id="wpstp_edit_frm" action="?page=<?php echo sanitize_text_field($_GET['page']); ?>&edit=<?php echo $id; ?>#list" method="post">
			<table class="widefat" cellspacing="0" id="">
				<thead>
				<tr>
					<th>Name</th>
					<th>Search String</th>
					<th>Spider URL</th>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<td scope="row"><input class="regular-text" type="text" name="name" size="20" maxlength="120" value="<?php echo $bot->name; ?>" /><br />
									The name you wish to give your spider i.e.: GoogleBot
					</td>
					<td scope="row"><input class="regular-text" type="text" name="search_str" size="20" maxlength="120" value="<?php echo $bot->search_str; ?>" /><br />
									A unique string that will identify a spider based on their UserAgent i.e.: use googlebot for Google's bot. See <a href="http://www.robotstxt.org/" target="_blank">robotstxt.org</a> for more info on spiders.
					</td>
					<td scope="row"><input class="regular-text" type="text" name="url" size="45" maxlength="255" value="<?php echo $bot->url; ?>" /><br />
									URL to the spider's home/support page
					</td>
					<td scope="row"><input class='button-primary' type="submit" value="Save changes" /></td>
				</tr>
				</thead>
			</table>
			<input type="hidden" name="wpstp_id" value="<?php echo $id; ?>" />
			<input type="hidden" name="wpstp_submitted_edit" value="yes" />
			</form>
			
			<?php
			
	}else {
	
			$b_color = get_option( "wpstp_pct_bar_color" );
			$log_state = get_option( "wpstp_log_state" );
			?>
			<form id="wpstp_setts_frm" action="?page=<?php echo sanitize_text_field($_GET['page']); ?>" method="post">
			<table class="widefat">
				<thead>
				<tr>
					<th>Tracking:</th>
				</tr>
				</thead>
			</table>
			<table class="form-table">
				<tr>
					<th scope="row">Max pages per spider:</th>
					<td width="100"><input type="text" name="wpstp_max_rows_per_spider" size="3" maxlength="3" value="<?php echo get_option( "wpstp_max_rows_per_spider" ); ?>" /></td>
					<td>Defines how many of the latest index entries to keep in the database.</td>
				</tr>
			</table>
			<table class="widefat">
				<thead>
				<tr>
					<th>Visual:</th>
				</tr>
				</thead>
			</table>
			<table class="form-table">
				<tr>
					<th scope="row">Default stats log state:</th>
					<td width="100"><select name="wpstp_log_state">
							<option value="c"<?php if ( $log_state == "c" ) echo " SELECTED"; ?>>collapsed</option>
							<option value="e"<?php if ( $log_state == "e" ) echo " SELECTED"; ?>>expanded</option>
						</select></td>
					<td>Defines the default loading state for the stats tables. Currently set as {<strong><?php
							if( $log_state == 'c' ) echo "collapsed"; else echo "expanded";
						?></strong>}</td>
				</tr>
				<tr>
					<th scope="row">Slide effect delay:</th>
					<td width="100"><input type="text" name="wpstp_slide_delay" size="5" maxlength="4" value="<?php echo get_option( "wpstp_slide_delay" ); ?>" /></td>
					<td>The higher the value the longer it takes to expand and collapse the stats.</td>					
				</tr>
				<tr>
					<th scope="row">Default percentage bar color:</th>
					<td width="100"><select name="wpstp_pct_bar_color">
							<option value="red"<?php if ( $b_color == "red" ) echo " SELECTED"; ?>>red</option>
							<option value="green"<?php if ( $b_color == "green" ) echo " SELECTED"; ?>>green</option>
							<option value="blue"<?php if ( $b_color == "blue" ) echo " SELECTED"; ?>>blue</option>
							<option value="yellow"<?php if ( $b_color == "yellow" ) echo " SELECTED"; ?>>yellow</option>
						</select></td>
					<td>Defines the percentage bar color. Current value is {<strong><?php echo $b_color; ?></strong>}</td>
				</tr>
				<tr>
					<td>
						<div class="submit"><input class='button-primary' type="submit" value="Save Settings" /></div>
					</td>
					<td align="right" colspan="2">
						<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6581225" target="_blank"><img src="<?php echo $WPSTP_PATH; ?>/images/paypal_donate_cc.gif" border=0 alt="Your generosity is appreciated!" /></a>
					</td>
				</tr>
			</table>
			
			<input type="hidden" name="wpstp_submitted_setts" value="yes" />
			</form>

			<?php
			// Add new spider to be tracked
			?>
			<a name="new"></a><h2>Add a new spider to track</h2>
			<?php 
			// Display 'new spider' messages at the header of the form
			if( $msg_new ) echo '<div id="message" class="updated fade"><p>'.$msg_new.'</p></div>';
			?>
			<form id="wpstp_new_frm" action="?page=<?php echo sanitize_text_field($_GET['page']); ?>#new" method="post">

			<table class="widefat" cellspacing="0" id="">
				<thead>
				<tr>
					<th>Name</th>
					<th>Search String</th>
					<th>Spider URL</th>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<td scope="row"><input class="regular-text" type="text" name="name" size="20" maxlength="120" value="" /><br />
									The name you wish to give your spider i.e.: GoogleBot
					</td>
					<td scope="row"><input class="regular-text" type="text" name="search_str" size="20" maxlength="120" value="" /><br />
									A unique string that will identify a spider based on their UserAgent i.e.: use googlebot for Google's bot. See <a href="http://www.robotstxt.org/" target="_blank">robotstxt.org</a> for more info on spiders.
					</td>
					<td scope="row"><input class="regular-text" type="text" name="url" size="45" maxlength="255" value="" /><br />
									URL to the spider's home/support page
					</td>
					<td scope="row"><input class='button-primary' type="submit" value="Track spider" /></td>
				</tr>
				</thead>
			</table>
			<input type="hidden" name="wpstp_submitted_new" value="yes" />
			</form>
			
			<a name="list"></a><h2>List of all Active and Inactive spiders</h2>
			<?php
			// Display 'list' messages at the header of the list table
			if( $msg_list ) echo '<div id="message" class="updated fade"><p>'.$msg_list.'</p></div>';
			
			echo '	<table class="widefat" cellspacing="0" id="">
						<thead>
						<tr>
							<th>&nbsp;</th>
							<th>Name</th>
							<th nowrap="nowrap">Search String</th>
							<th nowrap="nowrap">Spider URL</th>
							<th nowrap="nowrap">Last Index</th>
							<th nowrap="nowrap">Index Count</th>
							<th>Active</th>
							<th>Action</th>
						</tr>
						</thead>
						<tbody>';
			$rows = $wpdb->get_results( "SELECT * FROM `".$table_name."` WHERE 1 ORDER BY `name` ASC" );						
			foreach( $rows as $row ) {						
				echo '	<tr>
							<td width="20"><img src="'.$WPSTP_PATH.'/images/icon_info.png" alt="'.$row->useragent.'" border=0></td>
							<td><a name="'.$row->id.'"></a>'.$row->name.'</td>
							<td>'.$row->search_str.'</td>
							<td><a href="'.$row->url.'" target="_blank">'.$row->url.'</a></td>							
							<td>'.$row->last_index_time.'</td>
							<td>'.$row->index_count.'</td>
							<td>';			
							if( $row->active == 'y' ) {
								echo '<a href="?page='.sanitize_text_field($_GET['page']).'&active=n&id='.$row->id.'#'.$row->id.'">
										<img src="'. $WPSTP_PATH .'/images/icon_green.png" border=0 alt="Deactivate" /></a>';
							}else {
								echo '<a href="?page='.sanitize_text_field($_GET['page']).'&active=y&id='.$row->id.'#'.$row->id.'">
										<img src="'. $WPSTP_PATH .'/images/icon_red.png" border=0 alt="Activate" /></a>';
							}
				echo '		</td>
							<td>
								<a href="?page='.sanitize_text_field($_GET['page']).'&show=edit&id='.$row->id.'">
									<img src="'. $WPSTP_PATH .'/images/icon_edit.png" border=0 alt="Edit" /></a>
								<a href="?page='.sanitize_text_field($_GET['page']).'&amp;reset='.$row->id.'#list">
									<img src="'. $WPSTP_PATH .'/images/icon_reset.png" onclick="return confirm(\'Are you sure you want to reset all stats for '.addslashes($row->name).'?\nThis will reset the index count to zero and delete all index entries.\');" border=0 alt="Reset Stats" /></a>
								<a href="?page='.sanitize_text_field($_GET['page']).'&amp;delete='.$row->id.'#list">
									<img src="'. $WPSTP_PATH .'/images/icon_delete.png" onclick="return confirm(\'Are you sure you want to completely delete '.addslashes($row->name).' and all its index entries?\');" border=0 alt="Delete" /></a>';
				echo '		</td>
						</tr>';
			} // end foreach						
			echo '		</tbody>
					</table>';
	
			echo '</div><p>&nbsp;</p>';
		}
		
}
?>